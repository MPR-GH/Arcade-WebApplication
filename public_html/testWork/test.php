<?php
echo "keywords are not case sensitive<br>";
// Echo "Echo<br />";
// eCho "eCho<br />";
// ecHo "ecHo<br />";
// echO "echO<br />";
// ECho "ECho<br />";
// EcHo "EcHo<br />";
// EchO "EchO<br />";
// ECHo "ECHo<br />";
// EChO "EChO<br />";
// ECHo "ECHo<br />";
// ECHO "ECHO<br />";
echo "variables are case sensitive<br />";
$age = 16;
$AGE = 18;
$str = "4.1";
echo "age = ".$age."<br />";
echo "AGE = ".$AGE."<br />";
$testVar = $str + $age;
echo $testVar."<br>";
print($age==$AGE);
echo "var_dump(\$age)<br>";
var_dump($age);
echo "<br>";
var_dump($testVar);
$cars = array("hello","world");
echo "<br>";
var_dump($cars);
echo "<br>";
class Car {
    public $color;
    public $model;
    public function __construct($color, $model) {
        $this->color = $color;
        $this->model = $model;
    }
    public function message() {
        return "My car is a " . $this->color . " " . $this->model . "!<br />";
    }
}

$myCar = new Car("black", "Volvo");
echo $myCar -> message();
echo "<br>";
$myCar = new Car("red", "Toyota");
echo $myCar -> message();

class pizza {
    public $sauce;
    public $ingredient;
    public function __construct($ingredient, $sauce)    {
        $this -> sauce = $sauce;
        $this -> ingredient = $ingredient;
    }
    public function change($ingredient, $sauce) {
        $this -> sauce = $sauce;
        $this -> ingredient = $ingredient;
    }
    public function order() {
        echo "Sauce : ".$this->sauce." Ingedient : ".$this->ingredient."<br />";
        return;
    }
}
$pizza = new pizza("cheese","tomatoe");
$pizza->order();
$pizza->change("spinach", "tomatoe");
$pizza->order();

echo "<br>";
$x;
var_dump($x);
echo "<br>";
$x = "Hello a world afh";
echo strlen($x);
echo "<br>";
echo str_word_count($x);
echo "<br>";
echo strrev($x);
echo "<br />";
$x = 54;
echo PHP_INT_MAX;
echo "<br />";
echo PHP_INT_MIN;
echo "<br />";
echo PHP_INT_SIZE;
echo "<br />";
echo is_int($x);
//ignore this, it's just for output formatting
function newline(){
  //attempt to create newline for command line or browser, can ignore
  echo "<br>\n";
}
$message = "hello world";//global scope

//this section defines a function
function test(){
  echo "My global variable has $message";
}
//these executes/runs the function
test();
newline();
//output should be missing "hello world"
function test2(){
 $message = "Hello world from inside test2()";//this is a local scope variable
  echo $message;
}
test2();
newline();
//output should be "Hello world from inside test2();
//but we don't have access to the local variable
echo $message;
newline();
//will result in just "hello world"
//however if we do
function test3(){
  global $message;
  $message = "Hello world overriden from local";
  echo $message;
}
newline();
test3();
newline();
echo $message;
newline();
//both should output the same text

//finally lets count with static
function increment(){
  static $count = 0;
  echo "Next: $count";
  newline();
  $count++;
}
increment();
increment();
increment();
increment();
?>