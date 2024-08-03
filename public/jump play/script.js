const dino = document.getElementById('dino');
const scoreDisplay = document.getElementById('score');
let isJumping = false;
let isGameOver = false;
const gravity = 0.9;
let score = 0;
const speed = 10;

const jumpSound = new Audio('sounds/jump.mp3');
const gameOverSound = new Audio('sounds/gameover.mp3');
const backgroundMusic = new Audio('sounds/background.mp3');
backgroundMusic.loop = true;
backgroundMusic.play();

function jump() {
    if (isJumping) return;
    isJumping = true;
    jumpSound.play();

    let position = 0;
    const timerId = setInterval(() => {
        if (position >= window.innerHeight * 0.2) {
            clearInterval(timerId);

            const downTimerId = setInterval(() => {
                if (position <= 0) {
                    clearInterval(downTimerId);
                    isJumping = false;
                }
                position -= 5;
                position = position * gravity;
                dino.style.bottom = `${10 + position}%`;
            }, 20);
        }

        position += 30;
        position = position * gravity;
        dino.style.bottom = `${10 + position}%`;
    }, 20);
}

function generateObstacle() {
    const gameContainer = document.querySelector('.game-container');
    const obstacle = document.createElement('div');
    obstacle.classList.add(Math.random() > 0.5 ? 'cactus' : 'bird');
    gameContainer.appendChild(obstacle);

    let obstaclePosition = gameContainer.offsetWidth;
    const randomTime = Math.random() * 4000;

    const obstacleInterval = setInterval(() => {
        if (obstaclePosition < -40) {
            clearInterval(obstacleInterval);
            obstacle.remove();
            score++;
            scoreDisplay.innerText = score;
        }

        obstaclePosition -= speed;
        obstacle.style.left = `${obstaclePosition}px`;

        if (
            obstaclePosition > 0 &&
            obstaclePosition < 10 + dino.offsetWidth &&
            dino.offsetTop + dino.offsetHeight >= gameContainer.offsetHeight - obstacle.offsetHeight - 20
        ) {
            clearInterval(obstacleInterval);
            isGameOver = true;
            gameOverSound.play();
            alert('Game Over');
            document.location.reload();
        }
    }, 20);

    if (!isGameOver) setTimeout(generateObstacle, randomTime);
}

function control(e) {
    if (e.keyCode === 32) {
        jump();
    }
}

document.addEventListener('keydown', control);
generateObstacle();
