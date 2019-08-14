<?php

$installer = $this;
$installer->startSetup();

$installer->run("
DROP TABLE IF EXISTS {$installer->getTable('sirv/cache')};
CREATE TABLE {$installer->getTable('sirv/cache')} (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(255) NOT NULL DEFAULT '',
  `last_checked` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `url` (`url`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;
");

$connection = $installer->getConnection();
$result = $connection->query("SELECT * FROM `{$installer->getTable('core/config_data')}` WHERE `scope_id`=0 AND `path`='sirv/general/image_folder'");
if($result) {
    $rows = $result->fetch(PDO::FETCH_ASSOC);
    if(!$rows) {
        $validCharacters = 'abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ';
        $validCharNumber = strlen($validCharacters);
        $hashLength = 5;
        $randomHash = '';
        for($i = 0; $i < $hashLength; $i++) {
            $charIndex = mt_rand(0, $validCharNumber-1);
            $randomHash .= $validCharacters[$charIndex];
        }
        $installer->setConfigData('sirv/general/image_folder', "magento-{$randomHash}", 'default', 0);
        //$installer->run("INSERT INTO `{$installer->getTable('core/config_data')}` VALUES (NULL, 'default', 0, 'sirv/general/image_folder', 'magento-{$randomHash}');");
    }
}

$installer->endSetup();
