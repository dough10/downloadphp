<?php 
use App\Models\Db;
require __DIR__ . '../../vendor/autoload.php';
header('Content-Type: application/javascript'); 
session_start();
$db = new Db();
?>
const user = '<?= "User: " . $_SESSION['username'] ?>';
const id = '<?= "Session ID: " . session_id(); ?>';
const previous = <?= json_encode($db->getDownloads()); ?>.length;

const lsState = Number(localStorage.getItem('sound'));

let licenseDisplayed = false;

const soundLicense = '<--\nacess denied buzz by Jacco18\nhttps://freesound.org/s/419023/\nLicense: Creative Commons 0\n-->';

let sound = Boolean(lsState);

function toggleSoundFX() {
  sound = !sound;
  if (sound && !licenseDisplayed) {
    licenseDisplayed = true;
    console.log(soundLicense);
  }
  localStorage.setItem('sound', Number(sound));
  return `SoundFX: ${sound ? 'On':'Off'}`;
}

if (sound && !licenseDisplayed) {
  licenseDisplayed = true;
  console.log(soundLicense);
}
console.log(`${user}\n${id}\nPrevious downloads: ${previous}`);