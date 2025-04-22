-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1:3306
-- 產生時間： 2025-03-20 03:09:29
-- 伺服器版本： 8.3.0
-- PHP 版本： 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `mydb`
--
CREATE DATABASE IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;
USE `mydb`;

-- -------------------------------------------------------

--
-- 資料表結構 `tb_accounts`
--

DROP TABLE IF EXISTS `tb_accounts`;
CREATE TABLE IF NOT EXISTS `tb_accounts` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `username` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `password` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `isAdmin` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci COMMENT='The table for user accounts';

--
-- 傾印資料表的資料 `tb_accounts`
--

INSERT DELAYED INTO `tb_accounts` (`user_id`, `fullname`, `username`, `password`, `isAdmin`) VALUES
(1, 'Test', 'Test', '5f4dcc3b5aa765d61d8327deb882cf99', 1);

-- -------------------------------------------------------

--
-- 資料表結構 `tb_owned_products`
--

DROP TABLE IF EXISTS `tb_owned_products`;
CREATE TABLE IF NOT EXISTS `tb_owned_products` (
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `session` varchar(32) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
  `purchased_date` timestamp NOT NULL DEFAULT (convert_tz(now(),_utf8mb4'+00:00',_utf8mb4'-00:00')),
  UNIQUE KEY `idx_user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;

--
-- 傾印資料表的資料 `tb_owned_products`
--

INSERT DELAYED INTO `tb_owned_products` (`user_id`, `product_id`, `session`, `purchased_date`) VALUES
(1, 1, '', '2025-02-12 16:52:21'),
(1, 2, '', '2025-02-12 16:52:21'),
(1, 3, '', '2025-02-12 16:52:21'),
(1, 4, '', '2025-02-12 16:52:21'),
(1, 5, '', '2025-02-12 16:52:21');

--
-- 觸發器 `tb_owned_products`
--
DROP TRIGGER IF EXISTS `reduce_stock`;
DELIMITER $$
CREATE TRIGGER `reduce_stock` AFTER INSERT ON `tb_owned_products` FOR EACH ROW BEGIN
    -- Prevent stock from reducing below 0 and ignore digital products
    IF (SELECT stock FROM tb_products WHERE product_id = NEW.product_id) IS NOT NULL 
       AND (SELECT stock FROM tb_products WHERE product_id = NEW.product_id) > 0
       AND (SELECT isDigital FROM tb_products WHERE product_id = NEW.product_id) = 0 THEN
        UPDATE tb_products 
        SET stock = stock - 1 
        WHERE product_id = NEW.product_id;
    ELSEIF (SELECT stock FROM tb_products WHERE product_id = NEW.product_id) <= 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Stock cannot go below zero';
    END IF;
END
$$
DELIMITER ;

-- -------------------------------------------------------

--
-- 資料表結構 `tb_products`
--

DROP TABLE IF EXISTS `tb_products`;
CREATE TABLE IF NOT EXISTS `tb_products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `cardID` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cardTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `cardText` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `itemPrice` int NOT NULL,
  `isDigital` tinyint(1) NOT NULL,
  `imageSrc` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `imageAlt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `stock` int DEFAULT NULL,
  `YTLink` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_zh_0900_as_cs,
  PRIMARY KEY (`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- 傾印資料表的資料 `tb_products`
--

INSERT DELAYED INTO `tb_products` (`product_id`, `cardID`, `cardTitle`, `cardText`, `itemPrice`, `isDigital`, `imageSrc`, `imageAlt`, `stock`, `YTLink`, `description`) VALUES
(1, 'PR2S', 'Phantom Rose 2 Sapphire', 'A roguelike deckbuilding game. Fight and collect powerful cards as Aria, trying to survive in her cherished school that\'s being ravaged by evil creatures', 125, 1, 'Multimedia/PR2S.jpg', 'Phantom Rose 2', NULL, 'https://www.youtube.com/embed/rWcVg_f7cjs?si=Zxz37', 'NON'),
(2, 'OH', 'Once Human', 'A multiplayer open-world survival game set in a strange, post-apocalyptic future. Unite with friends to fight monstrous enemies, uncover secret plots, compete for resources, and build your own territory. Once, you were merely human. Now, you have the power to remake the world.', 0, 1, 'Multimedia/Once_Human.jpg', 'Once Human', NULL, '', '<span>\r\n    《七日世界》是一款多人線上開放世界生存遊戲，遊戲發生在一個怪誕的末世背景下。你需要和好友並肩為了生存資源而戰，建造你們的家園領地，並一起克服恐怖的畸變體，揭露世界淪陷的真相。曾經，你是人類中的一分子。現在，你仍是嗎？\r\n</span>    <span>\r\n    <a href=\"https://store.steamchina.com/app/2139460/_/\" class=\"button\" style=\"text-decoration: none;border: 2px solid #007bff;border-radius: 5px;display: inline-block; background-color: transparent; \">\r\n        免費開玩\r\n    </a>\r\n</span>\r\n<p>\r\n    關於這款遊戲\r\n    <br>浩劫改變了星球上的一切，人類，動物，植物……所有生命體都被神秘的外來物質－「星塵」所侵入。作為一個“進化者”，你對星塵的抵禦能力遠甚常人，但這並不意味著你是安全的。在末日中你可以作為獨狼求生，或聯合其他人一同戰鬥，建造，探索。當世界陷入了末日的混沌之時，你是我們最後的希望。\r\n    <br>星球隕落，你是荒野中的求生者\r\n    <br>你在一片荒野中醒來，飢渴難耐，周圍的瓜果與流水觸手可及，卻隱現詭異的藍光——這些被星塵污染的飲食不僅會損害健康，還會使你的理智逐漸崩潰… ……更多的危險蟄伏在暗處，等待著你力竭倒地後撲上來將你分食。進化者，別讓它們如願。\r\n</p><img style=\"display: block;\" src=\"https://shared.cdn.steamchina.eccdnx.com/store_item_assets/steam/apps/2139460/extras/GIF1-%E5%BC%80%E6%94%BE%E4%B8%96%E7%95%8C_%E6%9C%AB%E6%97%A5.gif?t=1728359060\">\r\n<p>\r\n    銀之門洞開，你是挑戰異位生物的戰士\r\n    <br>星塵浩劫滋生出難以計量的畸變體，這些恐怖的怪物幾乎佔據了整個世界。必須從它們手中收復人類的故土！雖然畸變體一直將人類視為獵物，但現在，進化者，這世界有了你…該換我們成為獵人了。\r\n</p>\r\n<img style=\"display: block;\" src=\"https://shared.cdn.steamchina.eccdnx.com/store_item_assets/steam/apps/2139460/extras/GIF2-PVE.gif?t=1728359060\">'),
(3, 'NoFM', 'Night of Full Moon', 'A stand-alone roguelike card game. Take adventure into Black Forest. Random event, random plot leads to different endings.', 120, 1, 'Multimedia/NoFM.jpg', 'Night of Full Moon', NULL, '', '<span>《月圓之夜》是一款獨立單機卡牌遊戲，探索，冒險，隨機事件，隨機劇情將會導致不同的結局。七大職業，每個職業有專屬的卡組，專屬的流派，100種獨具特色的怪物，揭秘黑森林的秘密。</span>\r\n        <a href=\"https://store.steamchina.com/app/769560/_/\" class=\"button\" style=\"text-decoration: none;border: 2px solid #007bff;border-radius: 5px;display: inline-block; background-color: transparent; \">\r\n            免費開玩\r\n        </a>\r\n        <p>\r\n            合輯內容：\r\n            <ol>\r\n                <li>魔術師</li>\r\n                <li>藥劑師</li>\r\n                <li>狼人</li>\r\n                <li>契約師</li>\r\n                <li>機械師</li>\r\n            </ol>\r\n        </p>\r\n        <p>\r\n            購買 更多內容: <a href=\"payment.php\" class=\"button\" style=\"text-decoration: none;border: 2px solid #007bff;border-radius: 5px;display: inline-block; background-color: transparent; \">\r\n                HK$ 120\r\n            </a>\r\n        </p>\r\n        <p>關於這款遊戲</p>\r\n        <img style=\"display: block; \" src=\"Multimedia/steam_background.png\" alt=\"月圓之夜\">\r\n        <p>\r\n            小紅帽從小就與外婆相依為命，但不幸的是有一天外婆神秘失蹤\r\n            <br>根據警衛隊的調查結果顯示：他們最後的線索都停留在黑森林\r\n            <br>為了尋找唯一的親人，小紅帽孤單一人前往黑森林，這一天正是月圓之夜\r\n            <br>她即將面對的是守護森林的精靈、兇殘的狼人、隱居的女巫、慢慢浮出水面的真相…\r\n        </p>\r\n        <img style=\"display: block; \" src=\"Multimedia/steam_feature.png\" alt=\"月圓之夜\">\r\n        <p>\r\n            這是一本自由探索的黑暗童話書\r\n            <br>* 一款 獨立單機 遊戲\r\n            <br>* 輕度 策略卡牌 戰鬥\r\n            <br>* 隨機 事件探索 冒險\r\n        </p>\r\n        <img style=\"display: block; \" src=\"Multimedia/steam_gameplay.png\" alt=\"月圓之夜\">\r\n        <p>\r\n            * 七大職業 探索事件隨機觸發\r\n            <br>* 女巫森林 童話精靈閃亮登場\r\n            <br>* 卡牌構築 多種流派自由搭配\r\n            <br>* 開放結局 碎片劇情還原真相\r\n        </p>\r\n        <img style=\"display: block; \" src=\"Multimedia/steam_developer.png\" alt=\"月圓之夜\">\r\n        <p>\r\n            我們想堅持做一款\"好玩\"而又\"良心\"的遊戲\r\n            <br>比起\"肝\"與\"氪\"，我們更喜歡\"策略\"與\"故事\"\r\n            <br>遊戲設計借鑒與學習了《Dream Quest》《卡牌冒險家》\r\n            <br>開發團隊於2017年11月17日前往美國約見《Dream Quest》作者 Peter Whalen\r\n            <br>獲得Peter的鼓勵與支持，爭取到了後續開發機會\r\n            <br>《卡牌冒險者》的設計師maou加入了我們團隊\r\n            <br>我們很珍惜做單機遊戲的機會\r\n            <br>希望這款遊戲能為大家帶來快樂\r\n        </p>'),
(4, 'Dota', 'Dota 2', 'Every day, millions of players worldwide enter battle as one of over a hundred Dota heroes. And no matter if it\'s their 10th hour of play or 1,000th, there\'s always something new to discover. With regular updates that ensure a constant evolution of gameplay, features, and heroes, Dota 2 has taken on a life of its own.', 0, 1, 'Multimedia/Dota2.jpg', 'Dota 2', NULL, '', '<span>\r\n            《刀塔2》是一款免費線上遊戲，支援Windows、Linux和Mac平台，適合13歲以上玩家，提供深度的戰術體驗。\r\n        </span><span>\r\n            完全免費，可以在<a href=\"https://store.steamchina.com/app/570/Dota_2/\" class=\"button\" style=\"text-decoration: none;border: 2px solid #007bff;border-radius: 5px;display: inline-block; background-color: transparent; \">\r\n                Steam平台下載。\r\n            </a>\r\n        </span>\r\n        <p></p>\r\n        <p>\r\n            關於這款遊戲\r\n            <br>在一個充滿魔法與英雄的世界中，玩家將操控各具特色的英雄，參與5v5的激烈對戰，探索與挑戰的奧秘。\r\n            <br>玩家選擇不同的英雄並在戰鬥中發揮各自的技能，進行戰略組合，目標是摧毀敵方基地。\r\n        </p>\r\n        <p>\r\n            一個戰場無限可能\r\n            <br>在英雄、技能和物品的多樣性方面，Dota可謂無與倫比——絕對不可能存在兩場相同的比賽。每個英雄都有多種定位打法，豐富的物品可以滿足每場比賽的特定需求。 Dota從不限制打法，只為讓您展現自己的風采。\r\n        </p>\r\n        <p>\r\n            所有英雄無需付費\r\n            <br>公平競技是Dota的基石，為確保所有人的遊戲基礎相同，遊戲的核心內容——如龐大的英雄數量——對所有玩家開放。粉絲們可以收集英雄飾品，和有趣的附加內容，但是進行遊戲所需的一切在開始第一場比賽前就已經賦予。\r\n        </p>\r\n        <p>\r\n            與好友攜手一同遊玩\r\n            <br>Dota內涵深邃，且不斷進化，但不論何時加入都不算太晚。\r\n            <br>合作對抗機器人可以模擬實戰。英雄試玩模式可以磨練技藝。而比賽的配對系統，兼顧玩家的遊戲行為和水平，確保每場比賽都能將合適的玩家配對在一起。\r\n        </p>\r\n        <span>評測</span><br>\r\n        <p>\r\n            “一款現代的多人遊戲傑作。”\r\n            <br>9.5/10 – Destructoid\r\n        </p>\r\n\r\n        <p>\r\n            “一旦開始了解其中奧秘，就會發現五花八門的玩法，同類遊戲望其項背。”\r\n            <br>9.4/10 – IGN\r\n        </p>\r\n\r\n        <p>\r\n            <br>“Dota 2很可能是唯一一款絲毫不被其商業模式拖累的競技類免費遊戲。”\r\n            <br>90/100 – PC Gamer\r\n        </p>'),
(5, 'CS', 'Counter-Strike: Global Offensive', 'For over two decades, Counter-Strike has offered an elite competitive experience, one shaped by millions of players from across the globe. And now the next chapter in the CS story is about to begin.', 102, 1, 'Multimedia/CSGO.jpg', 'CS:GO', NULL, '', '<span>二十多年來，在全球數百萬玩家的共同鑄就下，Counter-Strike 提供了精湛的競技體驗。而如今，CS 傳奇的下一章即將揭開序幕。</span>\r\n<a href=\"https://store.steamchina.com/app/730/_/\" class=\"button\" style=\"text-decoration: none;border: 2px solid #007bff;border-radius: 5px;display: inline-block; background-color: transparent; \">\r\n    免費開玩\r\n</a>\r\n<p>\r\n    購買 優先狀態升級: <a href=\"payment.php\" class=\"button\" style=\"text-decoration: none;border: 2px solid #007bff;border-radius: 5px;display: inline-block; background-color: transparent; \">\r\n        HK$ 103\r\n    </a>\r\n    <br>\r\n    優先狀態玩家將與其他優先狀態玩家進行匹配，並有資格獲得優先狀態玩家專屬紀念品、物品掉落和武器箱\r\n</p>\r\n<p>\r\n    這是反恐精英歷史上的技術大飛躍，未來幾年的新功能和優化都將得以保證。除了將Counter Strike 系列於 1999 年開創的經典遊戲玩法保留外，遊戲還會呈現以下特色：\r\n    <ol>\r\n        <li>全新 CS 綜合得分與經過更新的優先權模式</li>\r\n        <li>全球及區域排行榜</li>\r\n        <li>經過升級和大改的地圖</li>\r\n        <li>革命性的動態煙霧彈</li>\r\n        <li>不受刷新頻率阻礙的遊戲體驗</li>\r\n        <li>全新設計的聲畫效果</li>\r\n        <li>所有物品都有更好的顯示效果</li>\r\n    </ol>\r\n</p>');

--
-- 已傾印資料表的限制式
--

--
-- 資料表的限制式 `tb_owned_products`
--
ALTER TABLE `tb_owned_products`
  ADD CONSTRAINT `tb_owned_products_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tb_accounts` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_owned_products_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tb_products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;