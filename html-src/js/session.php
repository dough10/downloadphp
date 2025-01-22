<?php 
use App\Models\Db;
require __DIR__ . '/../../vendor/autoload.php';
header('Content-Type: application/javascript'); 
session_start();
$db = new Db();
?>
const user = '<?php echo "User: " . $_SESSION['username'] ?>';
const id = '<?php echo "Session ID: " . session_id(); ?>';
const previous = <?php echo json_encode($db->getDownloads()); ?>.length;
console.log(`${user}\n${id}\nPrevious downloads: ${previous}`);