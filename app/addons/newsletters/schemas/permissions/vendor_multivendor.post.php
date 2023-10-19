<?php
/***************************************************************************
*                                                                          *
*   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
*                                                                          *
* This  is  commercial  software,  only  users  who have purchased a valid *
* license  and  accept  to the terms of the  License Agreement can install *
* and use this program.                                                    *
*                                                                          *
****************************************************************************
* PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
* "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
****************************************************************************/

$schema['controllers']['import']['sections']['subscribers']['permission'] = false;
$schema['controllers']['export']['sections']['subscribers']['permission'] = false;

$schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['newsletter_campaigns'] = false;
$schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['mailing_lists'] = false;

$schema['controllers']['exim']['modes']['export']['param_permissions']['section']['subscribers'] = false;
$schema['controllers']['exim']['modes']['import']['param_permissions']['section']['subscribers'] = false;

return $schema;
