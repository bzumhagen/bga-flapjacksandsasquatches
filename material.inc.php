<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FlapjacksAndSasquatches implementation : © Benjamin Zumhagen bzumhagen@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * FlapjacksAndSasquatches game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 * NOTE: Card counts are based on physical card count from owner's copy.
 * Current total: 133 cards (100 red + 33 trees)
 * Expected total per game box: 136 cards
 * TODO: Verify actual card counts with complete/official copy (missing 3 cards)
 * Likely candidates for adjustment: Chopping Axe, Winded, Flapjacks, or Debunk
 */

// Red cards (Jack Deck) - card definitions with quantities
$this->red_cards = [
  'equipment_chopping_axe' => [
    'id' => 1,
    'type' => 'equipment',
    'subtype' => 'axe',
    'name' => clienttranslate("Chopping Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll."),
    'qty' => 5,
    'sprite_position' => 0  // Row 0, Col 0
  ],
  'plus_minus_winded' => [
    'id' => 2,
    'type' => 'plus_minus',
    'name' => clienttranslate("Winded"),
    'tooltip' => clienttranslate("Subtract two dice from your next chopping roll."),
    'modifier' => -2,
    'persistent' => false,
    'qty' => 3,
    'sprite_position' => 1  // Row 0, Col 1
  ],
  'help_apprentice' => [
    'id' => 5,
    'type' => 'help',
    'name' => clienttranslate("Apprentice"),
    'tooltip' => clienttranslate("Roll one separate die in addition to your chopping roll each turn."),
    'qty' => 4,
    'sprite_position' => 4  // Row 0, Col 4
  ],
  'plus_minus_footslip' => [
    'id' => 6,
    'type' => 'plus_minus',
    'name' => clienttranslate("Footslip"),
    'tooltip' => clienttranslate("Subtract two dice from your next chopping roll."),
    'modifier' => -2,
    'persistent' => false,
    'preventable_by' => 'boots',
    'qty' => 5,
    'sprite_position' => 5  // Row 0, Col 5
  ],
  'action_paul_bunyan' => [
    'id' => 9,
    'type' => 'action',
    'name' => clienttranslate("Paul Bunyan"),
    'tooltip' => clienttranslate("All trees in play are chopped down."),
    'qty' => 1,
    'sprite_position' => 9  // Row 1, Col 0
  ],
  'action_steal_equipment' => [
    'id' => 10,
    'type' => 'action',
    'name' => clienttranslate("Steal Equipment"),
    'tooltip' => clienttranslate("Take one equipment card in play from any player."),
    'qty' => 3,
    'sprite_position' => 10  // Row 1, Col 1
  ],
  'sasquatch_that_darn_sasquatch' => [
    'id' => 11,
    'type' => 'sasquatch',
    'name' => clienttranslate("That Darn Sasquatch"),
    'tooltip' => clienttranslate("All equipment in play is discarded."),
    'qty' => 2,
    'sprite_position' => 11  // Row 1, Col 2
  ],
  'action_axe_break' => [
    'id' => 13,
    'type' => 'action',
    'name' => clienttranslate("Axe Break"),
    'tooltip' => clienttranslate("Player's axe in play must be discarded."),
    'qty' => 4,
    'sprite_position' => 13  // Row 1, Col 4
  ],
  'equipment_gloves' => [
    'id' => 14,
    'type' => 'equipment',
    'subtype' => 'protection',
    'name' => clienttranslate("Gloves"),
    'tooltip' => clienttranslate("Prevents Blisters and Axe Slip."),
    'qty' => 5,
    'sprite_position' => 14  // Row 1, Col 5
  ],
  'equipment_carpenters_axe' => [
    'id' => 17,
    'type' => 'equipment',
    'subtype' => 'axe',
    'name' => clienttranslate("Carpenter's Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll."),
    'qty' => 5,
    'sprite_position' => 18  // Row 2, Col 0
  ],
  'equipment_dull_axe' => [
    'id' => 18,
    'type' => 'equipment',
    'subtype' => 'axe',
    'name' => clienttranslate("Dull Axe"),
    'tooltip' => clienttranslate("Subtract one die from your chopping roll. Dull Axe can be given to any player."),
    'modifier' => -1,
    'qty' => 5,
    'sprite_position' => 19  // Row 2, Col 1
  ],
  'equipment_swedish_broad_axe' => [
    'id' => 19,
    'type' => 'equipment',
    'subtype' => 'axe',
    'name' => clienttranslate("Swedish Broad Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll."),
    'qty' => 5,
    'sprite_position' => 20  // Row 2, Col 2
  ],
  'equipment_titanium_axe' => [
    'id' => 20,
    'type' => 'equipment',
    'subtype' => 'axe',
    'name' => clienttranslate("Titanium Axe"),
    'tooltip' => clienttranslate("Add one die to your chopping roll. Titanium Axe cannot be broken."),
    'modifier' => 1,
    'unbreakable' => true,
    'qty' => 3,
    'sprite_position' => 21  // Row 2, Col 3
  ],
  'plus_minus_blisters' => [
    'id' => 21,
    'type' => 'plus_minus',
    'name' => clienttranslate("Blisters"),
    'tooltip' => clienttranslate("Subtract one die from your chopping roll each turn."),
    'modifier' => -1,
    'persistent' => true,
    'removable_by' => 'gloves',
    'qty' => 4,
    'sprite_position' => 22  // Row 2, Col 4
  ],
  'action_lure_help' => [
    'id' => 22,
    'type' => 'action',
    'name' => clienttranslate("Lure Help"),
    'tooltip' => clienttranslate("Steal Apprentice or Long Saw and Partner."),
    'qty' => 5,
    'sprite_position' => 23  // Row 2, Col 5
  ],
  'sasquatch_sasquatch_mating_season' => [
    'id' => 25,
    'type' => 'sasquatch',
    'name' => clienttranslate("Sasquatch Mating Season"),
    'tooltip' => clienttranslate("Player loses a turn. You may take over this player's tree and discard your own."),
    'qty' => 2,
    'sprite_position' => 27  // Row 3, Col 0
  ],
  'plus_minus_axe_slip' => [
    'id' => 26,
    'type' => 'plus_minus',
    'name' => clienttranslate("Axe Slip"),
    'tooltip' => clienttranslate("Subtract one die from your next chopping roll."),
    'modifier' => -1,
    'persistent' => false,
    'preventable_by' => 'gloves',
    'qty' => 5,
    'sprite_position' => 28  // Row 3, Col 1
  ],
  'equipment_boots' => [
    'id' => 27,
    'type' => 'equipment',
    'subtype' => 'protection',
    'name' => clienttranslate("Boots"),
    'tooltip' => clienttranslate("Prevents foot slip."),
    'qty' => 5,
    'sprite_position' => 29  // Row 3, Col 2
  ],
  'action_steal_axe' => [
    'id' => 28,
    'type' => 'action',
    'name' => clienttranslate("Steal Axe"),
    'tooltip' => clienttranslate("Take one axe card in play from any player."),
    'qty' => 3,
    'sprite_position' => 30  // Row 3, Col 3
  ],
  'action_switch_tags' => [
    'id' => 29,
    'type' => 'action',
    'name' => clienttranslate("Switch Tags"),
    'tooltip' => clienttranslate("Exchange a chopped down tree with any player."),
    'qty' => 3,
    'sprite_position' => 31  // Row 3, Col 4
  ],
  'action_tree_hugger' => [
    'id' => 30,
    'type' => 'action',
    'name' => clienttranslate("Tree Hugger"),
    'tooltip' => clienttranslate("Player loses a turn."),
    'qty' => 4,
    'sprite_position' => 32  // Row 3, Col 5
  ],
  'reaction_debunk' => [
    'id' => 33,
    'type' => 'reaction',
    'name' => clienttranslate("Debunk"),
    'tooltip' => clienttranslate("Stops any Sasquatch card immediately."),
    'blocks' => 'sasquatch',
    'qty' => 3,
    'sprite_position' => 36  // Row 4, Col 0
  ],
  'plus_minus_flapjacks' => [
    'id' => 34,
    'type' => 'plus_minus',
    'name' => clienttranslate("Flapjacks"),
    'tooltip' => clienttranslate("Add two dice to your chopping roll this turn."),
    'modifier' => 2,
    'persistent' => false,
    'qty' => 3,
    'sprite_position' => 37  // Row 4, Col 1
  ],
  'action_forest_fire' => [
    'id' => 35,
    'type' => 'action',
    'name' => clienttranslate("Forest Fire"),
    'tooltip' => clienttranslate("All trees in play are destroyed."),
    'qty' => 1,
    'sprite_position' => 38  // Row 4, Col 2
  ],
  'help_long_saw_and_partner' => [
    'id' => 36,
    'type' => 'help',
    'name' => clienttranslate("Long Saw and Partner"),
    'tooltip' => clienttranslate("Set your axe aside and roll 5 dice. If on any turn you roll 4 misses or breaks, move this card to the player on your right."),
    'qty' => 1,
    'sprite_position' => 39  // Row 4, Col 3
  ],
  'reaction_paperwork' => [
    'id' => 37,
    'type' => 'reaction',
    'name' => clienttranslate("Paperwork"),
    'tooltip' => clienttranslate("Prevents Switch Tags and Tree Hugger immediately."),
    'blocks' => ['switch_tags', 'tree_hugger'],
    'qty' => 5,
    'sprite_position' => 40  // Row 4, Col 4
  ],
  'sasquatch_sasquatch_sighting' => [
    'id' => 38,
    'type' => 'sasquatch',
    'name' => clienttranslate("Sasquatch Sighting"),
    'tooltip' => clienttranslate("All opponents roll one die. Players that roll 1, 2, or 3 lose their next turn."),
    'qty' => 2,
    'sprite_position' => 41  // Row 4, Col 5
  ],
  'plus_minus_shortstack' => [
    'id' => 39,
    'type' => 'plus_minus',
    'name' => clienttranslate("Shortstack"),
    'tooltip' => clienttranslate("Add one die to your chopping roll this turn."),
    'modifier' => 1,
    'persistent' => false,
    'qty' => 4,
    'sprite_position' => 42  // Row 4, Col 6
  ],
];

// Tree cards (Tree Deck) - card definitions with quantities
$this->tree_cards = [
  'norway_pine' => [
    'id' => 1,
    'name' => clienttranslate("Norway Pine"),
    'chops_required' => 4,
    'points' => 4,
    'qty' => 8,
    'sprite_position' => 0  // Row 0, Col 0
  ],
  'american_elm' => [
    'id' => 2,
    'name' => clienttranslate("American Elm"),
    'chops_required' => 8,
    'points' => 8,
    'qty' => 2,
    'sprite_position' => 1  // Row 0, Col 1
  ],
  'cottonwood' => [
    'id' => 3,
    'name' => clienttranslate("Cottonwood"),
    'chops_required' => 5,
    'points' => 6,
    'qty' => 6,
    'sprite_position' => 2  // Row 0, Col 2
  ],
  'mighty_oak' => [
    'id' => 4,
    'name' => clienttranslate("Mighty Oak"),
    'chops_required' => 9,
    'points' => 12,
    'qty' => 1,
    'sprite_position' => 3  // Row 0, Col 3
  ],
  'red_oak' => [
    'id' => 5,
    'name' => clienttranslate("Red Oak"),
    'chops_required' => 6,
    'points' => 7,
    'qty' => 5,
    'sprite_position' => 4  // Row 1, Col 0
  ],
  'river_birch' => [
    'id' => 6,
    'name' => clienttranslate("River Birch"),
    'chops_required' => 4,
    'points' => 5,
    'qty' => 8,
    'sprite_position' => 5  // Row 1, Col 1
  ],
  'silver_maple' => [
    'id' => 7,
    'name' => clienttranslate("Silver Maple"),
    'chops_required' => 7,
    'points' => 8,
    'qty' => 3,
    'sprite_position' => 6  // Row 1, Col 2
  ]
];

// Card backs (sprite positions)
// TODO: Fix sprite sheets to use evenly divisible card dimensions to avoid fractional pixel alignment issues
// Current: red cards 358.44px × 500px, tree cards 358.25px × 499px
// Recommended: Use dimensions that divide evenly (e.g., 360px × 500px) for pixel-perfect alignment
$this->card_backs = [
  'red_card_back' => [
    'sprite' => 'red_cards.jpg',
    'sprite_position' => 8,  // Row 0, Col 8
    'cards_per_row' => 9
  ],
  'tree_card_back' => [
    'sprite' => 'tree_cards.jpg',
    'sprite_position' => 7,  // Row 1, Col 3
    'cards_per_row' => 4
  ]
];

// Game constants
$this->game_constants = [
  'winning_score' => 21,
  'starting_hand_size' => 3,
  'dice_result_break_min' => 1,
  'dice_result_break_max' => 2,
  'dice_result_miss' => 3,
  'dice_result_chop_min' => 4,
  'dice_result_chop_max' => 6,
  'axe_break_threshold' => 3,  // Number of breaks in one roll that breaks an axe
  'base_axe_dice' => 3,  // Standard axe rolls 3 dice
  'long_saw_dice' => 5,  // Long Saw & Partner rolls 5 dice
  'long_saw_break_threshold' => 4  // Long Saw breaks on 4+ misses/breaks combined
];

// Card deck locations
$this->card_locations = [
  'jack_deck' => 'deck',
  'tree_deck' => 'treedeck',
  'discard' => 'discard',
  'hand' => 'hand',
  'equipment' => 'equipment',
  'help' => 'help',
  'modifier' => 'modifier',
  'tree_active' => 'treeactive',
  'tree_cut' => 'treecut'
];
