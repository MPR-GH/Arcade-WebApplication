<?php
require(__DIR__ . "/../../partials/nav.php");
?>
<html>
    <head>
        <canvas id="canvas" width="600" height="400" tabindex="1"></canvas>
        <script>
            // Arcade Shooter game

            // Get a reference to the canvas DOM element
            var canvas = document.getElementById('canvas');
            // Get the canvas drawing context
            var context = canvas.getContext('2d');

            // Create an object representing a square on the canvas
            function makeSquare(x, y, length, speed) {
                return {
                    x: x,
                    y: y,
                    l: length,
                    s: speed,
                    draw: function() {
                    context.fillRect(this.x, this.y, this.l, this.l);
                    }
                };
            }

            // The ship the user controls
            var ship = makeSquare(50, canvas.height / 2 - 25, 50, 5);

            // Flags to tracked which keys are pressed
            var up = false;
            var down = false;
            var space = false;

            // Is a bullet already on the canvas?
            var shooting = false;
            // The bulled shot from the ship
            var bullet1 = makeSquare(0, 0, 10, 10);
            var bullet2 = makeSquare(0, 0, 10, 10);
            var bullet3 = makeSquare(0, 0, 10, 10);

            // An array for enemies (in case there are more than one)
            var enemies = [];
            var PUs = [];
            var lives = 3;
            var PUTripleShot = false;

            // Add an enemy object to the array
            var enemyBaseSpeed = 2;
            function makeEnemy() {
                var enemyX = canvas.width;
                var enemySize = Math.round((Math.random() * 15)) + 15;
                var enemyY = Math.round(Math.random() * (canvas.height - enemySize * 2)) + enemySize;
                var enemySpeed = Math.round(Math.random() * enemyBaseSpeed) + enemyBaseSpeed;
                enemies.push(makeSquare(enemyX, enemyY, enemySize, enemySpeed));
            }

            var PUBaseSpeed = 2;
            function makePU() {
                var PUX = canvas.width;
                var PUSize = Math.round((Math.random() * 15)) + 15;
                var PUY = Math.round(Math.random() * (canvas.height - PUSize * 2)) + PUSize;
                var PUSpeed = Math.round(Math.random() * PUBaseSpeed) + PUBaseSpeed;
                PUs.push(makeSquare(PUX, PUY, PUSize, PUSpeed));
            }

            // Check if number a is in the range b to c (exclusive)
            function isWithin(a, b, c) {
                return (a > b && a < c);
            }

            // Return true if two squares a and b are colliding, false otherwise
            function isColliding(a, b) {
            var result = false;
            if (isWithin(a.x, b.x, b.x + b.l) || isWithin(a.x + a.l, b.x, b.x + b.l)) {
                if (isWithin(a.y, b.y, b.y + b.l) || isWithin(a.y + a.l, b.y, b.y + b.l)) {
                    result = true;
                }
            }
                return result;
            }

            // Track the user's score
            var score = 0;
            // The delay between enemies (in milliseconds)
            var timeBetweenEnemies = 5 * 1000;
            var timeBetweenPUs = 5 * 5000;
            // ID to track the spawn timeout
            var timeoutId = null;

            // Show the game menu and instructions
            function menu() {
                erase();
                context.fillStyle = '#000000';
                context.font = '36px Arial';
                context.textAlign = 'center';
                context.fillText('Shoot \'Em!', canvas.width / 2, canvas.height / 4);
                context.font = '24px Arial';
                context.fillText('Click to Start', canvas.width / 2, canvas.height / 2);
                context.font = '18px Arial';
                context.fillText('Up/Down OR W/S OR D/A to move, Space OR click to shoot.', canvas.width / 2, (canvas.height / 4) * 3);
                // Start the game on a click
                canvas.addEventListener('click', startGame);
            }

            // Start the game
            function startGame() {
                // Kick off the enemy spawn interval
                timeoutId = setInterval(makeEnemy, timeBetweenEnemies);
                // Make the first enemy
                setTimeout(makeEnemy, 1000);
                timeoutId = setInterval(makePU, timeBetweenPUs);
                setTimeout(makePU, 5000);
                // Kick off the draw loop
                draw();
                // Stop listening for click events
                canvas.removeEventListener('click', startGame);
                canvas.addEventListener('click', shoot);
            }

            // Show the end game screen
            function endGame() {
                canvas.removeEventListener('click', startGame);
                // Stop the spawn interval
                clearInterval(timeoutId);
                // Show the final score
                erase();
                context.fillStyle = '#000000';
                context.font = '24px Arial';
                context.textAlign = 'center';
                context.fillText('Game Over. Final Score: ' + score, canvas.width / 2, canvas.height / 2);
                let http = new XMLHttpRequest();
                http.onreadystatechange = () => {
                    if (http.readyState == 4) {
                        if (http.status === 200) {
                            let data = JSON.parse(http.responseText);
                            console.log("received data", data);
                            flash(data.message, "success");
                            refreshBalance();
                        }
                        console.log(http);
                    }
                }
                http.open("POST", "api/save_score.php", true);
                let data = {
                    score: score
                }
                let q = Object.keys(data).map(key => key + '=' + data[key]).join('&');
                console.log(q)
                http.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                http.send(q);
            }

            // Listen for keydown events
            canvas.addEventListener('keydown', function(event) {
                event.preventDefault();
                if (event.keyCode === 38||event.keyCode === 87||event.keyCode === 65) { // UP
                    up = true;
                }
                if (event.keyCode === 40||event.keyCode === 83||event.keyCode === 68) { // DOWN
                    down = true;
                }
                if (event.keyCode === 32) { // SPACE
                    shoot();
                }
            });

            // Listen for keyup events
            canvas.addEventListener('keyup', function(event) {
                event.preventDefault();
                if (event.keyCode === 38||event.keyCode === 87||event.keyCode === 65) { // UP 
                    up = false;
                }
                if (event.keyCode === 40||event.keyCode === 83||event.keyCode === 68) { // DOWN
                    down = false;
                }
            });

            // Clear the canvas
            function erase() {
                context.fillStyle = '#FFFFFF';
                context.fillRect(0, 0, 600, 400);
            }

            // Shoot the bullet (if not already on screen)
            function shoot() {
                if (!shooting) {
                    shooting = true;
                    if(PUTripleShot)	{
                        bullet2.x = ship.x + ship.l;
                        bullet2.y = ship.y + ship.l;
                        bullet3.x = ship.x + ship.l;
                        bullet3.y = ship.y;
                    }
                    bullet1.x = ship.x + ship.l;
                    bullet1.y = ship.y + ship.l / 2;
                }
            }

            // The main draw loop
            function draw() {
                erase();
                var gameOver = false;
                // Move and draw the enemies
                enemies.forEach(function(enemy) {
                    enemy.x -= enemy.s;
                    if (enemy.x < 0) {
                        if(PUTripleShot)    {
                            PUTripleShot = false;
                            ship.l = 50;
                        }   else {
                            lives--;
                            if(lives == 0)  {
                                gameOver = true;
                            }
                        }
                    }
                    context.fillStyle = '#00FF00';
                    enemy.draw();
                });
                PUs.forEach(function(PU) {
                    PU.x -= PU.s;
                    const randomColor = Math.floor(Math.random()*16777215).toString(16);
                    context.fillStyle = "#" + randomColor;
                    PU.draw();
                });
                // Collide the ship with enemies
                enemies.forEach(function(enemy, i) {
                    if (isColliding(enemy, ship)) {
                        if(PUTripleShot)    {
                            PUTripleShot = false;
                            enemies.splice(i, 1);
                            ship.l = 50;
                        }   else if(lives > 1)   {
                            lives--;
                            enemies.splice(i, 1);
                        }   else    {
                            gameOver = true;
                        }
                    }
                });
                PUs.forEach(function(PU, i) {
                    if (isColliding(PU, ship)) {
                        if(PUTripleShot)	{
                            score++;
                        }
                    PUs.splice(i, 1);
                    PUTripleShot = true;
                    ship.l = 75;
                    }
                });
                // Move the ship
                if (down) {
                    ship.y += ship.s;
                }
                if (up) {
                    ship.y -= ship.s;
                }
                // Don't go out of bounds
                if (ship.y < 0) {
                    ship.y = 0;
                }
                if (ship.y > canvas.height - ship.l) {
                    ship.y = canvas.height - ship.l;
                }
                // Draw the ship
                context.fillStyle = '#FF0000';
                ship.draw();
                // Move and draw the bullet
                if (shooting) {
                    // Move the bullet
                    bullet1.x += bullet1.s;
                    if(PUTripleShot)	{
                        bullet2.x += bullet2.s;
                        bullet3.x += bullet3.s;
                    }
                    // Collide the bullet with enemies
                    enemies.forEach(function(enemy, i) {
                    if (isColliding(bullet1, enemy)||(PUTripleShot && (isColliding(bullet2, enemy)||isColliding(bullet3, enemy)))) {
                        enemies.splice(i, 1);
                        score++;
                        shooting = false;
                        // Make the game harder
                        if (score % 10 === 0 && timeBetweenEnemies > 1000) {
                            clearInterval(timeoutId);
                            timeBetweenEnemies -= 1000;
                            timeoutId = setInterval(makeEnemy, timeBetweenEnemies);
                        } else if (score % 5 === 0) {
                            enemyBaseSpeed += 1;
                        }
                    }
                    });
                    PUs.forEach(function(PU,i)	{
                        if (isColliding(bullet1, PU)||(PUTripleShot && (isColliding(bullet2, PU)||isColliding(bullet3, PU)))) {
                            PUs.splice(i, 1);
                            shooting = false;
                    }
                    });
                    // Collide with the wall
                    if (bullet1.x > canvas.width||(PUTripleShot && (bullet2.x > canvas.width||bullet3.x > canvas.width))) {
                        shooting = false;
                    }
                    // Draw the bullet
                    context.fillStyle = '#0000FF';
                    bullet1.draw();
                    if(PUTripleShot)	{
                        bullet2.draw();
                        bullet3.draw();
                    }
                }
                // Draw the score
                context.fillStyle = '#000000';
                context.font = '24px Arial';
                context.textAlign = 'left';
                context.fillText('Score: ' + score, 1, 25)
                context.fillStyle = '#000000';
                context.font = '24px Arial';
                context.textAlign = 'right';
                context.fillText('Health: ' + lives, canvas.width - 15, 25)
                // End or continue the game
                if (gameOver) {
                    endGame();
                } else {
                    window.requestAnimationFrame(draw);
                }
            }

            // Start the game
            menu();
            canvas.focus();
        </script>
        <style>
            #canvas {
                width: 600px;
                height: 400px;
                border: 1px solid black;
            }
        </style>
    </head>
</html>