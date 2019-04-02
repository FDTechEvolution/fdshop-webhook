<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$username = 'Korn';
$cheers = [
                        sprintf('Saved! เยี่ยมมากน้อง %s', $username),
                        sprintf('อยากได้อีกน้อง %s ส่งมาอีก!', $username),
                        sprintf('สุดยอด! น้อง %s บันทึกแล้ว', $username),
                        sprintf('ชอบมาก อยากได้อีกน้อง %s', $username)
                    ];
                    $random_key = $cheers[rand(0, count($cheers) - 1)];
                    echo $random_key;