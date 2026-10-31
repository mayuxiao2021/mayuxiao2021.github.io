---
title: 从零搭一条 Android logcat 脱敏抓取管线
published: 2026-10-31
description: 当目标 App 自己在生产包里打印明文报文时，抓包就成了多余的一步。记录一次从 adb 脚本到手机端 App 的抓取工具演进，以及途中踩过的权限坑。
tags: [Android, 逆向, 工具链, logcat]
category: 安全研究
draft: false
---

# 从零搭一条 Android logcat 脱敏抓取管线

## 免责声明

本文记录的是**日志采集与数据脱敏的工具方法**，属于移动端测试的通用技术。
适用场景为：调试自己开发的应用、经授权测试自有或受托资产。

1. 请勿用于采集他人数据或未获授权的目标。
2. 文中所有字段、报文、标识符均为占位符或已脱敏，不含任何真实个人信息。
3. 采集他人数据在多数司法辖区涉及个人信息保护相关法律，请自行确认合规性。

---

## 0. 起因

做某个 App 的协议分析时，发现它在生产包里开了 `Log.d`，把**解密后的明文响应**直接打到 logcat。

这意味着常规的 MITM 流程——装证书、配代理、解 TLS、再解业务加密——全都多余了。

但直接 `adb logcat` 有几个现实问题：

1. **缓冲只有 256 KiB**，长会话会把前面的挤掉
2. **日志里全是 PII**，落到文件就是一份泄露
3. **手机要带走**，USB 得拔

于是就有了这条管线的三次迭代。

---

## 1. 第一版：PC 端管道

最直接的做法：

```bash
adb logcat -v threadtime -s <tag>:V | python sanitize.py out.log
```

`sanitize.py` 逐行正则替换：

```python
RULES = [
    (re.compile(r'(?<!\d)1[3-9]\d{9}(?!\d)'), '[PHONE]'),
    (re.compile(r'\$2[aby]\$\d{2}\$[./A-Za-z0-9]{53}'), '[PWDHASH]'),
    (re.compile(r'(?<!\d)\d{19}(?!\d)'), '[SNOWFLAKE_ID]'),
    (re.compile(r'"(userName|realName|nickName|password)":"[^"]*"'),
     lambda m: '"%s":"[REDACTED]"' % m.group(1)),
    # ...
]
```

### 坑一：编码

Windows 下管道会把 UTF-8 弄坏，中文全变 `鎿嶄綔鎴愬姛`。

```bash
export PYTHONIOENCODING=utf-8    # 必须
```

### 坑二：缓冲区

```bash
adb shell logcat -G 32M    # 默认 256 KiB，长会话必爆
adb shell logcat -g        # 确认
```

改完实测从 `256 KiB` 变 `32 MiB`。

---

## 2. 第二版：手机端 App（失败）

目标：让手机自己抓，跑步时不用带电脑。

思路很直接——写个 App，`Runtime.exec("logcat -s tag")` 读流，边读边脱敏。

### 撞墙：Android 的日志访问控制

实测结果是**读不到**。逐层排查：

| 测试 | 结果 |
|---|---|
| `pm grant <pkg> android.permission.READ_LOGS` | 返回 0，`granted=true` |
| 系统弹「允许访问所有设备日志吗？」 | 出现，点允许成功 |
| App 内 `exec("logcat")` | **仍读不到目标应用日志** |
| App 进程 SELinux 域 | `u:r:untrusted_app_32:s0` |
| 以 app uid 执行 `su` | `su: inaccessible or not found` |

原因：Android 4.1 起 `READ_LOGS` 收紧为 `signature|development` 级，
且 SEAndroid 层面 `untrusted_app` 域连 `su` 都执行不了。

**结论：普通应用无法读取其他进程的 logcat。** 这是设计意图，不是配置问题。

### 尝试 Shizuku（按官方文档）

既然普通 App 不行，就用 Shizuku——它以 shell(uid 2000) 常驻，而 shell 能读 logcat。

严格按 [Shizuku-API 文档](https://github.com/RikkaApps/Shizuku-API) 实现：

```xml
<provider
    android:name="rikka.shizuku.ShizukuProvider"
    android:authorities="${applicationId}.shizuku"
    android:multiprocess="false"
    android:enabled="true"
    android:exported="true"
    android:permission="android.permission.INTERACT_ACROSS_USERS_FULL" />
```

```java
// 授权走标准运行时权限模型
if (Shizuku.checkSelfPermission() == PackageManager.PERMISSION_GRANTED) {
    // 已授权
} else {
    Shizuku.requestPermission(REQ_CODE);
}
```

用 `UserService`（`newProcess` 已在 13.1.1 标记废弃）在 shell 进程中跑 logcat。

构建时踩到的依赖坑：Shizuku 的 AAR 需要四个：
`api` / `provider` / `aidl` / `shared`，少一个就会
`NoClassDefFoundError: moe.shizuku.server.IShizukuApplication$Stub`。

**结果：binder 没送到。**

服务端日志给到了决定性证据：

```
BinderSender: Uid 10392 starts
BinderSender: Uid 10392 already starts
```

服务端**检测到了**我们的 App 启动，但从未调用我们的 provider。

已排除的原因：

| 排查项 | 结果 |
|---|---|
| shell 是否持有 INTERACT_ACROSS_USERS_FULL | `granted=true` |
| provider 是否注册 | 已注册，shell 手动 `content call` 可通 |
| server 是否在 app 安装后重启 | 已重启 |
| 官方 adb 启动命令 | 用的是 Shizuku 界面给出的原命令 |

推测是定制 ROM 的进程/包可见性限制。**代码没问题，环境不给。**

---

## 3. 第三版：文件监视（成功）

换一个思路——既然 shell 能读 logcat、普通 App 能读**自己的外部私有目录**，
那就让 shell 写、App 读。

```
shell(adb) ──写──> /sdcard/Android/data/<pkg>/files/raw.log
                            ↓
App tail 该文件（读自己目录零权限）→ 逐行脱敏 → 显示/保存
```

关键验证：

```bash
# shell 能否写入 App 的外部目录
adb shell "mkdir -p /sdcard/Android/data/<pkg>/files"
adb shell "echo test > /sdcard/Android/data/<pkg>/files/probe.txt"
# → -rw-rw---- 1 shell ext_data_rw    ← 可以
```

这条路**不需要 root、不需要 Shizuku、不需要任何特殊权限**。

App 侧只做三件事：

```java
// 1. 从文件读（tail 语义）
BufferedReader br = new BufferedReader(
        new InputStreamReader(new FileInputStream(rawFile), "UTF-8"));

// 2. 逐行脱敏
String s = Sanitizer.clean(line);

// 3. 写脱敏文件
w.write(s); w.write('\n');
```

`tail -f` 的循环要注意 EOF 不是结束：

```java
while (running) {
    String line = br.readLine();
    if (line == null) {
        Thread.sleep(400);   // 文件暂时没有新内容，等一会儿再读
        continue;
    }
    // 处理
}
```

### 最终形态

```bash
# 出门前（插着 USB）
bash phone_capture_start.sh

# 手机 App 点「开始抓取」→ 拔 USB → 去跑步
# App 全程持续监视，实时脱敏

# 回来点「停止并保存」
```

---

## 4. 脱敏规则设计

核心原则：**保留分析所需，消除身份相关**。

| 保留 | 消除 |
|---|---|
| 接口路径 | 姓名 |
| 参数结构 | 学号 |
| 响应 `code` / `msg` / `type` | 手机号 |
| 数值指标（配速/步数/里程/时长） | 密码哈希 |
| 枚举值 | 内部 ID（雪花 ID） |
| | token |
| | 精确坐标（保留 1 位小数） |
| | 长密文（保留前 12 字符便于对齐） |

实测验证清洗效果：

```bash
# 隐私项残留检查
grep -cE '1[3-9][0-9]{9}' clean.log      # → 0
grep -c '真实姓名' clean.log              # → 0
grep -cE '\$2a\$10\$' clean.log          # → 0
grep -cE '\b20[0-9]{8}\b' clean.log      # → 0

# 脱敏标记存在
grep -c '\[SNOWFLAKE_ID\]' clean.log     # → 24
grep -c '\[UUID\]' clean.log             # → 24
```

### 一个容易漏的点

日志里的键名可能不带引号：

```
backSource:"lwEUgOia/..."      ← 不是 "backSource":"..."
```

第一版正则写成 `"backSource":"..."` 就漏了。要写成：

```python
re.compile(r'"?(\w+)"?:"([A-Za-z0-9+/=]{60,})"')
```

---

## 5. 三条路的对比

| | PC 脚本 | 手机 App + 文件监视 | Shizuku |
|---|---|---|---|
| 能抓到 | ✅ | ✅ | ❌ 本机未打通 |
| 跑步时需带电脑 | 否（拔了就行） | 否 | 否 |
| 需要 root | 否 | 否 | 否 |
| 需要特殊权限 | 否 | 否 | 需 Shizuku |
| 自动脱敏 | ✅ | ✅ | ✅ |
| 复杂度 | 低 | 中 | 高 |

**PC 脚本 + 文件监视** 是最终选择：出门前跑一次脚本，之后手机独立工作。

---

## 6. 小结

几点体会：

1. **先看应用自己打了什么日志。** 很多 App 的日志比抓包信息量还大——尤其是有加密的情况下，抓包只能看到密文，日志里有解密后的。

2. **Android 的日志隔离是认真的。** 不要指望 `pm grant READ_LOGS` 能绕过去，那是设计上的墙。

3. **绕不过就换方向。** 与其跟 SELinux 死磕，不如让有权限的一方（shell）写、没权限的一方（App）读自己的目录。

4. **脱敏必须在采集侧做。** 等落到磁盘再清理，中间那段时间就是泄露窗口。

5. **验证比推断重要。** 我一开始假设"普通 App 能读 logcat"，写了半天才去验证，白费工夫。先花 5 分钟做个最小验证，比改一天代码划算。

---

## 附：本文涉及的最小可用代码

```python
#!/usr/bin/env python3
import sys, re, io

RULES = [
    (re.compile(r'(?<!\d)1[3-9]\d{9}(?!\d)'), '[PHONE]'),
    (re.compile(r'\$2[aby]\$\d{2}\$[./A-Za-z0-9]{53}'), '[PWDHASH]'),
    (re.compile(r'(?<!\d)\d{19}(?!\d)'), '[SNOWFLAKE_ID]'),
    (re.compile(r'(?<![0-9A-Fa-f])[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}'
                r'-[0-9A-F]{4}-[0-9A-F]{12}(?![0-9A-Fa-f])', re.I), '[UUID]'),
    (re.compile(r'"(userName|realName|nickName|phonenumber|password)":"[^"]*"'),
     lambda m: '"%s":"[REDACTED]"' % m.group(1)),
]

def clean(line):
    for pat, rep in RULES:
        line = pat.sub(rep, line)
    return line

out = sys.argv[1]
with io.open(out, 'w', encoding='utf-8') as f:
    for line in sys.stdin:
        if '(HKS)' in line:      # 某些 ROM 会给日志块加密，跳过
            continue
        f.write(clean(line.rstrip('\n')) + '\n')
```

```bash
export PYTHONIOENCODING=utf-8
adb shell logcat -G 32M
adb logcat -v threadtime -s <tag>:V | python sanitize.py out.log
```
