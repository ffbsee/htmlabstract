<?php
/**
 * metadata configuration for the htmlabstract plugin
 * @author Vincent Feltz <psycho@feltzv.fr>
 * @author Lilian Roller <l3d@see-base.de>
 */

$meta['paragraph']  = array('onoff');
$meta['maxlength']  = array('numeric', '_pattern' => '/\d{2,4}/');
$meta['textlink'] = array('string', '_pattern' => '/[^<>&]*/');
$meta['bg_color'] = array('string', '_pattern' => '/[0-9a-f]{6}/');
