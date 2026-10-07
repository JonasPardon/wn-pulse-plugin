<?php

return [
    /*
     * Which signed-in user Pulse attributes requests and jobs to, checked in
     * this order. "frontend" needs the Winter.User plugin and is skipped
     * without it. An empty array turns user attribution off.
     */
    'users' => ['frontend', 'backend'],
];
