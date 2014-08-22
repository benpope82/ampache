<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$base_url = '?action=set_userflag&userflag_type=' . $userflag->type . '&object_id=' . $userflag->id;
$flagged = $userflag->get_flag();
$flagValue = $flagged ? '0' : '1';
?>

<span class="userflag">
<?php
    echo '<i id="userflag_'. $userflag->id.'" class="fa fa-heart' . (!$flagged ? '-o' : ' on') . '"></i>';
    echo Ajax::run('$("#userflag_'. $userflag->id .'").click(function () { saveFlagIcons("'. Ajax::url($base_url . '&userflag=' . $flagValue) .'", "userflag_'. $userflag->id .'", ' . ($flagged ? 'false' : 'true') . ');});');
?>
</span>
