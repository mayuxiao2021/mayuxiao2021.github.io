---
title: 某校园跑步 App 安卓客户端安全审计：从静态还原到实机验证
published: 2026-10-31
description: 一次完整的移动端安全审计实践——国密 SM2/SM4 协议逆向、客户端反作弊架构剖析、生产包日志泄露，以及静态分析与动态验证的差异复盘。
tags: [Android, 逆向, 安全审计, 国密, 隐私]
category: 安全研究
draft: false
---

# 某校园跑步 App 安卓客户端安全审计

## 免责声明

本文为**安全研究与技术学习**记录，写作目的仅限：

- 分析移动端加密实现与反作弊架构的**技术原理**
- 指出客户端实现层面的**安全缺陷**，供开发者改进防护
- 记录一次静态分析与动态验证的**方法论复盘**

明确声明如下：

1. **本文不包含、也不主张任何绕过考核、伪造运动数据或规避检测的方法。**
   文中不提供可执行的操作步骤，所涉检测机制仅从「如何修复」的角度讨论。
2. 全部测试均在**本人账号、本人设备**上进行，未使用他人账号，未采集他人数据。
3. 文中所有报文、截图与字段示例均已脱敏：校名、姓名、学号、手机号、密码哈希、
   ID、token、精确坐标、服务器地址一律移除或替换为占位符。
4. **客户端密钥仅描述「如何验证其真实性」的数学方法，不公开任何实际密钥值。**
5. 本文**不鼓励、不支持**任何违反校规校纪、学术诚信或相关法律法规的行为。
   体育考核的目的在于健康，请通过正常途径完成。
6. 若本文内容涉及任何单位或个人的合法权益，请通过博客联系方式告知，将立即处理。

> 读者应自行判断所在地区法律法规与所在单位的规章制度。
> 因不当使用本文信息产生的后果，由使用者自行承担。

---

## 0. 背景

校园跑步类 App 是一个很有意思的审计对象：

- 它采集**位置轨迹、传感器数据、人脸信息、设备指纹**——隐私敏感度高
- 它有**反作弊考核**，业务逻辑复杂
- 它是学生被强制使用的，用户没有选择权

我针对自己所在高校使用的某款校园跑步 App（3.6.7 版本）做了一次完整审计，
覆盖静态反编译与实机动态验证两个阶段。

**结论先行**：

| 项 | 结论 |
|---|---|
| 判分逻辑位置 | 完全在服务端，客户端只采集上报 |
| 客户端加密 | 国密 SM2 + SM4，设计思路合理但密钥硬编码 |
| 生产包日志 | **开启 BODY 级日志，明文打印全部请求与解密后响应** |
| 反作弊检测 | 客户端三道检测，**可用性依赖服务端开关** |
| 设备指纹上报 | 上报 `devEnable` / `rootEnable` / 虚拟定位 App 名单 |

---

## 1. 审计方法

### 1.1 工具链

| 环节 | 工具 |
|---|---|
| 大包快筛 | Droid ASC（零预处理交叉引用搜索） |
| 反编译 | Droid ASC `getclass` / `disassemble` + aapt2 |
| Manifest 解析 | `aapt2 dump xmltree` |
| 密码学验证 | Python 纯实现 SM2 曲线标量乘法 |
| 动态抓取 | logcat + 自建脱敏管线 |

APK 大小 182 MB，7 个 DEX 分片（合计约 54 MB），无加固壳，未混淆
（Application 类与业务类名可读，这省了很多事）。

### 1.2 为什么先做快筛

182 MB 的包直接 JADX 全量反编译，耗时与内存都很夸张。
Droid ASC 的思路是把 APK 当只读数据库查询，不建全局索引：

```bash
# 全局交叉引用搜索，秒级返回
droidasc findrefs <apk> string splitPoint
droidasc findrefs <apk> method runNewSendPoint
droidasc findrefs <apk> string devEnable

# 按需反编译单个类
droidasc getclass <apk> com.example.sportrun.module.running.SportRunMapActivity
```

命中之后才付全量成本。实际只反编译了 20 余个类就覆盖了核心逻辑。

---

## 2. 发现一：生产包开启 BODY 级日志

这是最容易验证、影响也最直接的一条。

自定义 OkHttp 拦截器里：

```java
HttpLoggingInterceptor logger = new HttpLoggingInterceptor();
logger.setLevel(HttpLoggingInterceptor.Level.BODY);   // ← 不是 BASIC，是 BODY
```

并且拦截器自己又打印了一遍：

```java
Log.d("snow", "params:" + bodyUtf8);          // 明文请求
Log.d("snow", "response:" + decrypted);       // ← 解密后的明文响应
```

日志开关没有做 release 判断：

```java
public static boolean b = True;   // 静态初值 true
```

### 后果

任意能执行 `adb logcat` 的人，可以拿到**解密后的完整业务报文**。

实测抓到的一条登录响应：

```json
{"userName":"<学号>","nickName":"<姓名>","phonenumber":"<手机号>",
 "password":"<BCrypt哈希>",   // ← 密码哈希也在日志里
 "realName":"<姓名>","schoolName":"<校名>","className":"<班级>"}
```

**姓名、学号、手机号、BCrypt 密码哈希，全在日志里。**

而且该 App 集成了崩溃采集 SDK，日志有随崩溃上报的可能性，泄露面不止本地。

### 修复建议

- `HttpLoggingInterceptor` 仅在 `BuildConfig.DEBUG` 下挂载
- 移除自定义拦截器的 `Log.d`
- 日志开关绑定 `BuildConfig.DEBUG`
- 必须保留时做脱敏（token / 手机号 / 身份证 / 坐标）

---

## 3. 发现二：国密协议实现与密钥硬编码

### 3.1 密钥来源链

```java
public class JNI {
    static { System.loadLibrary("crs-sdk"); }        // 10 KB 的 native 库
    public static native String getMD5Key(Context c);   // appsecret
    public static native String getPrivateKey(Context c); // SM2 私钥
    public static native String getPublicKey(Context c);  // SM2 公钥
}
```

库名叫 "crs-sdk"，看起来像内容保护 SDK，但符号表里直接写着：

```
PRIVATE_KEY   PUBLIC_KEY   MD5KEY   AUTH_KEY
```

密钥以**明文 Base64** 存在 `.rodata`。

### 3.2 验证密钥是否真实（关键一步）

反编译拿到一段 Base64，不能直接当成结论——它可能是占位符、测试值、或者已废弃的旧密钥。

我用 Python 纯实现 `sm2p256v1` 曲线的标量乘法，验证私钥与公钥是否配对：

```python
p  = 0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFF
a  = 0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF00000000FFFFFFFFFFFFFFFC
b  = 0x28E9FA9E9D9F5E344D5A9E4BCF6509A7F39789F515AB8F92DDBCBD414D940E93
n  = 0xFFFFFFFEFFFFFFFFFFFFFFFFFFFFFFFF7203DF6B21C6052B53BBF40939D54123
Gx = 0x32C4AE2C1F1981195F9904466A39C9948FE30BBFF2660BE1715A4589334C74C7
Gy = 0xBC3736A2F4F6779C59BDCEE36B692153D0A9877CC62A474002DF32E52139F0A0

# 从 PKCS#8 DER 中取出私钥标量 d 和内嵌公钥
Q = d * G

print("keypair matches:", Q.x == embedded_x and Q.y == embedded_y)
```

结果**逐字节一致**——密钥对真实有效，不是占位符。

这一步很重要：没有这步，写出来的报告就只是"疑似"。

### 3.3 协议结构（已完整还原）

**签名**：

```java
TreeMap<String,String> m;
m.put("platform", "android");
m.put("utc", System.currentTimeMillis()/1000 + "");
m.put("uuid", UUID.randomUUID().toString().toUpperCase());

sign = md5Hex("platform=android&utc=<ts>&uuid=<uuid>&appsecret=" + md5Key);
```

**请求体双层加密**：

```
sessionKey = 每次请求随机生成的 SM4 密钥
cipherKey  = SM2_Encrypt(Base64(sessionKey), 公钥)
content    = SM4_Encrypt_Base64(请求 JSON)
body       = {"content": content, "cipherKey": cipherKey}
```

**响应**：SM4 解密，部分接口额外 unGzip。

### 3.4 问题在哪

加密本身没问题——SM2 密钥交换 + SM4 会话加密 + MD5 签名，思路是对的。

问题在于**密钥全部硬编码在客户端且可完整提取**，导致"加密即混淆"：

- `sign` 里没有任何用户身份因子，`appsecret` 全员相同 → 签名不提供身份绑定
- 真正的身份边界只有 `token`

> 按行业惯例，「APK 中含硬编码密钥」本身通常不进计分项。
> 它的价值在于：这是后续所有接口测试的前置条件——拿到密钥与签名算法，
> 就具备了构造任意合法请求的能力。是否构成越权，取决于服务端鉴权实现。

---

## 4. 发现三：反作弊模块架构

核心类都在 `module.running.RunDetector` 包下：

| 类 | 检测内容 | 实现 |
|---|---|---|
| `RootDetector` | root | 遍历 8 条 su 路径 + `command -v su` |
| `DeveloperOptionsDetector` | 开发者选项 | `Settings.Global.getInt(cr,"development_settings_enabled",0)==1` |
| `MockLocationDetector` | mock provider | 每 5 秒拉 GPS，`Location.isFromMockProvider()` 计数 |
| `KnownMockLocationApps` | 已安装虚拟定位 App | 硬编码 15 个包名，逐个 `getApplicationInfo` 探测 |
| `JTDetector` | 总控 | 由服务端下发的 `avoidId` 字符串控制 |

### 4.1 硬编码黑名单

15 条虚拟定位工具包名，包含市面主流工具及其改版。

### 4.2 服务端开关 `avoidId`

```java
public static boolean isEnabled(Context ctx, String avoidId) {
    if (avoidId == null || avoidId.length() < 4) return true;
    String s1 = avoidId.substring(1, 2);   // 开发者选项
    String s2 = avoidId.substring(2, 3);   // root
    String s3 = avoidId.substring(3, 4);   // 虚拟定位 App
    // 任一位为 "1" 且对应检测命中 → 弹窗并返回 false
}
```

**三道检测全由服务端配置控制**——这个设计是对的，不同学校/不同风控强度可以分级。

实测环境下该配置项的值为 `1000`，即三道检测均未启用。
需要说明的是，这是**服务端可下发的运行时配置**，不同学校、不同学期可独立调整，
本文仅记录单次观测结果，不对任何具体单位的策略做评价或推断。

### 4.3 实现缺陷

`KnownMockLocationApps.getInstalledApps()`：

```java
Iterator it = map.entrySet().iterator();
while (it.hasNext()) {
    Map.Entry e = (Map.Entry) it.next();
    pm.getApplicationInfo((String) e.getKey(), 0);   // ← 未捕获 NameNotFoundException
    list.add((String) e.getValue());
}
```

对**未安装**的包名，`getApplicationInfo` 必然抛 `NameNotFoundException`。
黑名单里 15 个包全装才不抛异常——正常设备必然触发。

这属于工程质量问题，与"是否该绕过"无关。

---

## 5. 发现四：判分完全在服务端

这是本次审计最有价值的结论。

### 5.1 接口定位

反编译 `API` 接口时发现一个坑：DAD 反编译不显示注解，接口路径全丢了。
必须走 smali 才能看到：

```smali
.method public abstract runNewSendPoint(Lokhttp3/RequestBody;)Lio/reactivex/Observable;
    .annotation runtime Lretrofit2/http/POST;
        value = "run/splitPointCheating"
    .end annotation
```

**客户端方法名和接口路径完全对不上**，靠注解才能关联。

### 5.2 请求结构

```json
{
  "crsRunRecordId": "...", "userName": "...", "schoolId": "...",
  "mileage": 512.3, "times": 152, "StepNumber": 340,
  "speeds": 4.94, "runSteps": 134.2, "strides": 1.51,
  "cardPointList": [
    {
      "point": "经度,纬度",
      "speed": "5.8",
      "runStatus": "1",
      "runTime": 0,
      "runStep": 0,
      "runMileage": 0.0,
      "isFence": "N",
      "isRunMock": "0",
      "ts": "1793000000"
    }
  ]
}
```

客户端算好 6 个聚合指标 + 逐点原始轨迹，每段上传一次。

配速公式（double 常量被编译成整数，需要逐个反解）：

```
4652007308841189376 = 1000.0
4633641066610819072 = 60.0
4588807732320345784 = 0.06
4651127699538968576 = 900.0
```

```java
speeds   = (times/60.0) / (mileage/1000.0);   // min/km
runSteps = StepNumber / (times/60.0);          // 步/分
strides  = mileage / StepNumber;               // 米/步
```

### 5.3 客户端没有任何判分逻辑

穷举了客户端所有钳制点：

| 位置 | 内容 | 阈值 | 后果 |
|---|---|---|---|
| `h2()` | `isFromMockProvider()` | 布尔 | 标 `isRunMock=1`，**只上报不阻断** |
| `g2()` | 瞬时配速 | 1.0 ~ 900.0 | 钳制 |
| `W2()` | 相邻点距离 | 35 m | 丢 pathline 重算里程 |

**没有一处判断"这算不算作弊"。** 全部交给服务端。

---

## 6. 发现五：实测拿到真实判罚规则

静态分析到这里就断了——服务端算法拿不到。于是做了实机动态验证。

### 6.1 抓取方式

利用发现一的日志缺陷，不需要 MITM、不需要装证书、不需要 root：

```bash
adb logcat -s snow:V | python sanitize.py out.log
```

拿到的是**已解密的明文报文**。

### 6.2 判罚规则（服务端明文下发）

`run/isStandard` 直接返回全部规则：

```json
{
  "msg": "本次跑步未满足要求",
  "isStandard": "N",
  "list": [
    {"msg": "至少跑2.0公里"},
    {"msg": "平均步频在40步/分钟和300步/分钟之间"},
    {"msg": "配速应在3.0min/km和10.0min/km之间;"},
    {"msg": "踩点数至少3个点"}
  ]
}
```

四条硬阈值：**距离 / 步频 / 配速 / 踩点数**。

需要强调：这四条**并非写死的通用规则**，而是服务端按学校下发的任务配置
（对应 `raSingleMileageMin/Max`、`raCadenceMin/Max`、`raPaceMin/Max`、`raMinDislikes` 等字段），
不同学校、不同学期完全可以不同。本文列出仅为说明「判分依据的类型」，
不构成任何可跨场景套用的标准。

### 6.3 隐藏的环境指纹上报

`run/finish` 请求体末尾有个不在任何 Java bean 里的字段：

```json
"extra": "{\"shkScore\":\"0\",\"shkName\":\"{}\",
           \"devEnable\":\"1\",\"rootEnable\":\"0\",\"emulatorName\":\"\"}"
```

对应代码在 `BaseCheckUtil.detectResult()`：

```java
v2_3.put("shkScore", score);
v2_3.put("shkName",  evidence);
boolean devEnable = DeveloperOptionsDetector.isEnabled(ctx);
boolean rooted    = RootDetector.isRooted();
String  mockApps  = KnownMockLocationApps.getDetectedAppNames(ctx);
v2_3.put("devEnable",    devEnable ? "1" : "0");
v2_3.put("rootEnable",   rooted    ? "1" : "0");
v2_3.put("emulatorName", mockApps);   // 字段名有误导，实际是虚拟定位 App 名单
```

**每次提交都会把设备环境状态报给服务端并落库。**

---

## 7. 复盘：静态分析错在哪

这次最值得记录的一点——**我用静态分析得出了一个错误结论**。

### 错误结论

反编译 `SportRunMapActivity$a0.a()`：

```java
CanRerunBean bean = (CanRerunBean) p6.getData();
if (!"1".equals(bean.getCode())) {
    if (adapter.getDataSize() > 3) adapter.removeFirst();
    adapter.addData(bean);
}
```

我判断：`code == "1"` 表示「这批点被判作弊，静默丢弃」。

### 实测推翻

7 次调用**全部**返回 `{"data":{"code":"1"}}`，包括最终判定为「成绩合格」的那次。

如果 `"1"` 代表作弊，合格那次也该被判——矛盾。

**正确语义**：`"1"` = 正常无异常，只有 `code != "1"` 才展示警告。

### 教训

**反编译能读出分支结构，读不出枚举值语义。**

静态分析给你：

- 控制流：✅ 准确
- 字段结构：✅ 准确
- 常量值：✅ 准确
- **枚举语义：❌ 无法确定**

只有拿到真实响应才能纠正。这就是为什么纯静态的安全报告经常"看起来对但结论错"。

### 静态 vs 实测 对照

| 静态判断 | 实测 | 吻合 |
|---|---|---|
| 接口路径与请求字段 | 逐字段一致 | ✔ |
| 分段上传 + 服务端判分 | 证实 | ✔ |
| `isRunMock` 只上报不阻断 | 证实 | ✔ |
| 无本地判分算法 | 证实 | ✔ |
| **`code:"1"` = 判作弊** | **错误** | ✘ |

---

## 8. 防护建议汇总

| 优先级 | 项目 |
|---|---|
| **P0** | 移除 release 包的 BODY 级日志与明文输出 |
| **P0** | 服务端对所有接口强制校验 token，并对客户端传入的对象标识做归属校验 |
| **P1** | 反作弊检测补异常捕获，检测失败时默认从严而非崩溃 |
| **P1** | 网络安全配置：移除用户 CA 信任、关闭全局明文 HTTP |
| **P2** | 签名引入 token 派生因子，使 `sign` 具备身份绑定 |
| **P2** | 黑名单改服务端下发（当前硬编码，名单更新需发版且已完全暴露） |
| **P3** | 移除强制 `Proxy.NO_PROXY`；`allowBackup` 设 false |

---

## 9. 一点思考

这次审计让我印象最深的不是技术点，而是两个反差：

**第一，加密做得挺认真，但日志把一切揭开了。**

SM2 + SM4 + 签名，该有的都有。结果一个 `Log.d` 把解密后的明文全打到 logcat 里。
安全防护从来不是"最弱的一环"，而是"所有环节都成立"才算数。

**第二，判分放服务端是对的，但客户端把证据全交出去了。**

服务端手上是完整的逐点轨迹：经纬度、时间戳、瞬时配速、步数、里程、mock 标记。
这意味着它可以算任何东西——配速方差、步幅一致性、轨迹直线度、时间单调性。

客户端改什么都只能改上报值，改不了"这些值彼此之间必须自洽"这件事。

这个设计其实是合理的。它说明设计者清楚：**判罚必须留给服务端，客户端检测只能是辅助。**

---

## 附：审计产物

| 文件 | 内容 |
|---|---|
| 安全审计报告 | 密钥提取与验证、协议还原、反作弊模块披露 |
| 判分逻辑还原 | 静态分析，接口与字段全还原 |
| 实测验证报告 | 真实报文、判罚规则、隐藏上报字段、纠错记录 |

> 数据采集全程使用自建脱敏管线，文中所有报文均已移除
> 姓名、学号、手机号、密码哈希、ID、token、坐标与服务器地址。
