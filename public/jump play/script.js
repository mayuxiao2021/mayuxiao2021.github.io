const dino = document.getElementById('dino')
let isJumping = false
let isGameOver = false
// biome-ignore lint/style/useConst: <explanation>
let gravity = 0.9

function jump() {
  if (isJumping) return
  isJumping = true

  let position = 0
  // biome-ignore lint/style/useConst: <explanation>
  let timerId = setInterval(() => {
    if (position >= window.innerHeight * 0.2) {
      clearInterval(timerId)

      // biome-ignore lint/style/useConst: <explanation>
      let downTimerId = setInterval(() => {
        if (position <= 0) {
          clearInterval(downTimerId)
          isJumping = false
        }
        position -= 5
        position = position * gravity
        dino.style.bottom = `${position}px`
      }, 20)
    }

    position += 30
    position = position * gravity
    dino.style.bottom = `${position}px`
  }, 20)
}

function generateCactus() {
  // biome-ignore lint/style/useConst: <explanation>
  let cactus = document.createElement('div')
  cactus.classList.add('cactus')
  document.querySelector('.game-container').appendChild(cactus)
  let cactusPosition = document.querySelector('.game-container').offsetWidth
  // biome-ignore lint/style/useConst: <explanation>
  let randomTime = Math.random() * 4000

  // biome-ignore lint/style/useConst: <explanation>
  let cactusInterval = setInterval(() => {
    if (cactusPosition < -cactus.offsetWidth) {
      clearInterval(cactusInterval)
      cactus.remove()
    }

    cactusPosition -= 10
    cactus.style.left = `${cactusPosition}px`

    if (
      cactusPosition > 0 &&
      cactusPosition < 10 + dino.offsetWidth &&
      dino.offsetTop + dino.offsetHeight >=
        document.querySelector('.game-container').offsetHeight -
          cactus.offsetHeight
    ) {
      clearInterval(cactusInterval)
      isGameOver = true
      alert('Game Over')
    }
  }, 20)

  if (!isGameOver) setTimeout(generateCactus, randomTime)
}

function control(e) {
  if (e.keyCode === 32) {
    jump()
  }
}

document.addEventListener('keydown', control)
generateCactus()
