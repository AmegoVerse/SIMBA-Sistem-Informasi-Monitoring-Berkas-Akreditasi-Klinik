<?php
class Pesan extends Exception {}
function db(){
  static $c = null;
  if (!$c) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $c->set_charset('utf8mb4');
    $c->query("SET time_zone = '+08:00'");
  }
  return $c;
}
function q($sql, $p = []){
  $s = db()->prepare($sql);
  if ($p) {
    $t = '';
    foreach ($p as $v) { $t .= is_int($v) ? 'i' : 's'; }
    $s->bind_param($t, ...$p);
  }
  $s->execute();
  return $s;
}
function rows($sql, $p = []){
  $s = q($sql, $p);
  $r = $s->get_result();
  $a = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
  $s->close();
  return $a;
}
function row($sql, $p = []){ $a = rows($sql, $p); return $a ? $a[0] : null; }
function val($sql, $p = []){ $r = row($sql, $p); return $r ? array_values($r)[0] : null; }
function ex($sql, $p = []){ $s = q($sql, $p); $n = $s->affected_rows; $s->close(); return $n; }
function ins($sql, $p = []){ $s = q($sql, $p); $s->close(); return db()->insert_id; }
function in_ph($n){ return implode(',', array_fill(0, $n, '?')); }
