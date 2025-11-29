-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- FlapjacksAndSasquatches implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.


-- Card table for both Jack Deck (red cards) and Tree Deck
-- Uses BGA Deck library conventions
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(32) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(32) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- Player table extensions for game-specific data
ALTER TABLE `player` ADD `player_skip_next_turn` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_has_drawn_rampage` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_give_hand_to_player_id` INT(11) NULL DEFAULT NULL;
ALTER TABLE `player` ADD `player_axe_throw_bonus` INT(11) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `player_current_tree_id` INT(11) NULL DEFAULT NULL;


-- Player equipment tracking
-- Stores equipment cards in play for each player (axes, gloves, boots, etc.)
CREATE TABLE IF NOT EXISTS `player_equipment` (
  `equipment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `equipment_type` varchar(32) NOT NULL,
  PRIMARY KEY (`equipment_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- Player help cards tracking
-- Stores help cards in play (Apprentice, Babe, Long Saw & Partner)
CREATE TABLE IF NOT EXISTS `player_help` (
  `help_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `help_type` varchar(32) NOT NULL,
  PRIMARY KEY (`help_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- Player plus/minus modifiers tracking
-- Stores active plus/minus cards (Flapjacks, Blisters, etc.)
CREATE TABLE IF NOT EXISTS `player_modifiers` (
  `modifier_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `modifier_type` varchar(32) NOT NULL,
  `modifier_value` int(11) NOT NULL,
  `is_persistent` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`modifier_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- Active trees tracking
-- Stores trees currently being chopped by players
CREATE TABLE IF NOT EXISTS `active_tree` (
  `tree_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `tree_type` varchar(32) NOT NULL,
  `chop_count` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `chops_required` int(11) UNSIGNED NOT NULL,
  `points_value` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`tree_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- Cut trees (completed trees in player's score pile)
-- Tracked separately for potential card effects (Switch Tags)
CREATE TABLE IF NOT EXISTS `cut_tree` (
  `cut_tree_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `tree_type` varchar(32) NOT NULL,
  `points_value` int(11) UNSIGNED NOT NULL,
  `cut_order` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`cut_tree_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
