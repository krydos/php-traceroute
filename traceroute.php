<?php

define("SOL_IP", 0);
define("IP_TTL", 4);    // On OSX and BSDs use '4', For Linux - '2'.

$dest_addr = (isset($argv[1]) ? $argv[1] : 'localhost');
$maximum_hops = 30;
$port = 33434;

if (!filter_var($dest_addr, FILTER_VALIDATE_IP)) {
    $dest_addr = gethostbyname($dest_addr);
}

print "Trace to: $dest_addr\n";

$ttl = 1;
while ($ttl < $maximum_hops) {
    $icmp_socket = socket_create(AF_INET, SOCK_RAW, getprotobyname('icmp'));
    $udp_socket = socket_create(AF_INET, SOCK_DGRAM, getprotobyname('udp'));

    socket_set_option($udp_socket, SOL_IP, IP_TTL, $ttl);
    socket_bind($icmp_socket, 0, 0);

    $time = microtime(true);

    socket_sendto($udp_socket, "", 0, 0, $dest_addr, $port);

    $r = [$icmp_socket];
    $w = $e = [];
    socket_select($r, $w, $e, 5, 0);

    if (count($r)) {
        socket_recvfrom($icmp_socket, $buf, 512, 0, $recv_addr, $recv_port);
        $roundtrip_time = (microtime(true) - $time) * 1000;

        if (empty ($recv_addr)) {
            $recv_addr = "*";
            $recv_name = "*";
        } else {
            $recv_name = $recv_addr;
        }
        printf("%3d   %-15s  %.3f ms  %s\n", $ttl, $recv_addr, $roundtrip_time, $recv_name);
    } else {
        printf("%3d   (timeout)\n", $ttl);
    }

    socket_close($icmp_socket);
    socket_close($udp_socket);

    $ttl++;

    if ($recv_addr == $dest_addr) {
        break;
    }
}
