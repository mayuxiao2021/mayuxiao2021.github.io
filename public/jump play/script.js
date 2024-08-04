const dino = document.getElementById('dino')
const scoreDisplay = document.getElementById('score')
const startButton = document.getElementById('start-button')
const restartButton = document.getElementById('restart-button')
let isJumping = false
let isGameOver = false
const gravity = 0.9
let score = 0
let speed = 10
let gameRunning = false
let obstacleInterval

const jumpSound = new Audio('sounds/jump.mp3')
const gameOverSound = new Audio('sounds/gameover.mp3')
const backgroundMusic = new Audio('sounds/background.mp3')
backgroundMusic.loop = true

function startGame() {
  gameRunning = true
  isGameOver = false
  score = 0
  speed = 10
  scoreDisplay.innerText = score
  startButton.style.display = 'none'
  restartButton.style.display = 'none'
  backgroundMusic.play()
  generateObstacle()
  document.addEventListener('keydown', control)
}

function endGame() {
  isGameOver = true
  gameRunning = false
  clearInterval(obstacleInterval)
  gameOverSound.play()
  backgroundMusic.pause()
  restartButton.style.display = 'block'
  alert('Game Over')
}

function jump() {
  if (isJumping) return
  isJumping = true
  jumpSound.play()

  let position = 0 // 局部变量，避免作用域冲突
  const jumpInterval = 20 // 常量，跳跃间隔时间

  const timerId = setInterval(() => {
    if (position >= window.innerHeight * 0.2) {
      clearInterval(timerId)

      const downTimerId = setInterval(() => {
        if (position <= 0) {
          clearInterval(downTimerId)
          isJumping = false
        }
        position -= 5
        position = position * gravity
        dino.style.bottom = `${10 + position}%`
      }, jumpInterval)
    }

    position += 30
    position = position * gravity
    dino.style.bottom = `${10 + position}%`
  }, jumpInterval)
}

function generateObstacle() {
  const gameContainer = document.querySelector('.game-container')
  const obstacle = document.createElement('div')
  obstacle.classList.add(Math.random() > 0.5 ? 'cactus' : 'bird')
  gameContainer.appendChild(obstacle)

  let obstaclePosition = gameContainer.offsetWidth
  const randomTime = Math.random() * 4000

  obstacleInterval = setInterval(() => {
    if (obstaclePosition < -40) {
      clearInterval(obstacleInterval)
      obstacle.remove()
      score++
      scoreDisplay.innerText = score
    }

    obstaclePosition -= speed
    obstacle.style.left = `${obstaclePosition}px`

    if (
      obstaclePosition > 0 &&
      obstaclePosition < 10 + dino.offsetWidth &&
      dino.offsetTop + dino.offsetHeight >=
        gameContainer.offsetHeight - obstacle.offsetHeight - 20
    ) {
      clearInterval(obstacleInterval)
      endGame()
    }
  }, 20)

  if (!isGameOver) setTimeout(generateObstacle, randomTime)
}

function control(e) {
  if (e.keyCode === 32 && gameRunning) {
    jump()
  }
}

startButton.addEventListener('click', startGame)
restartButton.addEventListener('click', startGame)
