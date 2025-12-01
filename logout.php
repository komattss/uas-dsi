<?php
session_start();

// Bersihin session
$_SESSION = [];
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
Swal.fire({
    title: "Berhasil Logout",
    text: "Kamu akan diarahkan ke halaman login",
    icon: "success",
    timer: 1500,
    showConfirmButton: false
}).then(() => {
    window.location.href = "login.php";
});
</script>

</body>
</html>
