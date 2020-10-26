<?php
define("SKD_BASE", dirname(__FILE__));
define("ADAPAY_CORE", SKD_BASE."/../AdapayCore");
define("SDK_VERSION", "v1.0.3");
define("GATE_WAY_URL", "https://api.adapay.tech");
define("DEBUG", false);
define("LOG", dirname(SKD_BASE)."/log/prod");
define("ENV", "prod");

include_once ADAPAY_CORE."/AdaPay.php";
include_once ADAPAY_CORE."/AdaLoader.php";

include_once SKD_BASE."/MerchantConf.php";
include_once SKD_BASE."/MerchantUser.php";
