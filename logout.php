<?php
require __DIR__ . '/inc/mulai.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals(csrf(), (string)($_POST['_t'] ?? ''))) keluar();
redirect('index.php');
