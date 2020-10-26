<?php
define("SKD_BASE", dirname(__FILE__));
define("ADAPAY_CORE", SKD_BASE."/../AdapayCore");
define("SDK_VERSION", "v1.0.9");
define("GATE_WAY_URL", "https://api.adapay.tech");
define("DEBUG", true);
define("LOG", dirname(SKD_BASE)."/log/prod");
define("ENV", "prod");

include_once ADAPAY_CORE."/AdaPay.php";
include_once ADAPAY_CORE."/AdaLoader.php";

include_once SKD_BASE."/Payment.php";
include_once SKD_BASE."/Refund.php";
include_once SKD_BASE."/Member.php";
include_once SKD_BASE."/CropMember.php";
include_once SKD_BASE."/Tools.php";
include_once SKD_BASE."/SettleAccount.php";