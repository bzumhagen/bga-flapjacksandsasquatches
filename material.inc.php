<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FlapjacksAndSasquatches implementation : © <Your name here> <Your email address here>
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
 */


/*

Example:

$this->card_types = array(
    1 => array( "card_name" => ...,
                ...
              )
);

*/
$this->red_cards = [
  'equipment_chopping_axe' => [
    'id' => 1,
    'type' => 'equipment',
    'name' => clienttranslate("Chopping Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll.")
  ],
  'plus_minus_chopping_axe' => [
    'id' => 2,
    'type' => 'plus_minus',
    'name' => clienttranslate("Winded"),
    'tooltip' => clienttranslate("Subtract two dice from your next chopping roll.")
  ],
  'action_beavers' => [
    'id' => 3,
    'type' => 'action',
    'name' => clienttranslate("Beavers"),
    'tooltip' => clienttranslate("Discard any one player's tree currently in play.")
  ],
  'sasquatch_sasquatch_rampage' => [
    'id' => 4,
    'type' => 'sasquatch',
    'name' => clienttranslate("Sasquatch Rampage"),
    'tooltip' => clienttranslate("All players discard their hands and draw a new hand at the beginning of their next turn.")
  ],
  'help_apprentice' => [
    'id' => 5,
    'type' => 'help',
    'name' => clienttranslate("Apprentice"),
    'tooltip' => clienttranslate("Roll one separate die in addition to your chopping roll each turn.")
  ],
  'plus_minus_footslip' => [
    'id' => 6,
    'type' => 'plus_minus_footslip',
    'name' => clienttranslate("Footslip"),
    'tooltip' => clienttranslate("Subtract two dice from your next chopping roll.")
  ],
  'equipment_double_bladed_axe' => [
    'id' => 7,
    'type' => 'equipment',
    'name' => clienttranslate("Double Bladed Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll with one additional die.")
  ],
  'plus_minus_side_of_bacon' => [
    'id' => 8,
    'type' => 'plus_minus',
    'name' => clienttranslate("Side of Bacon"),
    'tooltip' => clienttranslate("Add one die to your next chopping roll (can be played with Flapjacks or Shortstack).")
  ],
  'action_paul_bunyan' => [
    'id' => 9,
    'type' => 'action',
    'name' => clienttranslate("Paul Bunyan"),
    'tooltip' => clienttranslate("All trees in play are chopped down.")
  ],
  'action_steal_equipment' => [
    'id' => 10,
    'type' => 'action',
    'name' => clienttranslate("Steal Equipment"),
    'tooltip' => clienttranslate("Take one equipment card in play from any player.")
  ],
  'sasquatch_that_darn_sasquatch' => [
    'id' => 11,
    'type' => 'sasquatch',
    'name' => clienttranslate("That Darn Sasquatch"),
    'tooltip' => clienttranslate("All equipment in play is discarded.")
  ],
  'contest_log_rolling' => [
    'id' => 12,
    'type' => 'contest',
    'name' => clienttranslate("Log Rolling"),
    'tooltip' => clienttranslate("Choose an opponent:</br>Both players roll one die. The lower roller loses next turn.")
  ],
  'action_axe_break' => [
    'id' => 13,
    'type' => 'axe_break',
    'name' => clienttranslate("Axe Break"),
    'tooltip' => clienttranslate("Player's axe in play must be discarded")
  ],
  'equipment_gloves' => [
    'id' => 14,
    'type' => 'equipment',
    'name' => clienttranslate("Gloves"),
    'tooltip' => clienttranslate("Prevents Blisters and Axe Slip")
  ],
  'action_babe_biscuit' => [
    'id' => 15,
    'type' => 'action',
    'name' => clienttranslate("Babe Biscuit"),
    'tooltip' => clienttranslate("Lure Babe from an opponent or the discard pile.")
  ],
  'help_babe' => [
    'id' => 16,
    'type' => 'help',
    'name' => clienttranslate("Babe"),
    'tooltip' => clienttranslate("Roll two separate dice in addition to your chopping roll each turn.")
  ],
  'equipment_carpenters_axe' => [
    'id' => 17,
    'type' => 'equipment',
    'name' => clienttranslate("Carpenter's Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll.")
  ],
  'equipment_dull_axe' => [
    'id' => 18,
    'type' => 'equipment',
    'name' => clienttranslate("Dull Axe"),
    'tooltip' => clienttranslate("Subtract one die from your chopping roll.</br>Dull Axe can be given to any player.")
  ],
  'equipment_swedish_broad_axe' => [
    'id' => 19,
    'type' => 'equipment',
    'name' => clienttranslate("Swedish Broad Axe"),
    'tooltip' => clienttranslate("Lets you make a chopping roll.")
  ],
  'equpment_titanium_axe' => [
    'id' => 20,
    'type' => 'equipment',
    'name' => clienttranslate("Titanium Axe"),
    'tooltip' => clienttranslate("Add one die to your chopping roll.</br>Titanium Axe cannot be broken.")
  ],
  'plus_minus_blisters' => [
    'id' => 21,
    'type' => 'plus_minus',
    'name' => clienttranslate("Blisters"),
    'tooltip' => clienttranslate("Subtract one die from your chopping roll each turn.")
  ],
  'action_lure_help' => [
    'id' => 22,
    'type' => 'action',
    'name' => clienttranslate("Lure Help"),
    'tooltip' => clienttranslate("Steal Apprentice or Long Saw and Partner.")
  ],
  'action_give_me_a_hand' => [
    'id' => 23,
    'type' => 'action',
    'name' => clienttranslate("Give Me a hand"),
    'tooltip' => clienttranslate("Choose an opponent:</br>On that player's next chopping roll one die must be used to chop your tree.")
  ],
  'contest_speed_climb' => [
    'id' => 24,
    'type' => 'contest',
    'name' => clienttranslate("Speed Climb"),
    'tooltip' => clienttranslate("Choose an opponent:</br>Both players roll one die. The higher roll wins 2 victory points.")
  ],
  'sasquatch_sasquatch_mating_season' => [
    'id' => 25,
    'type' => 'sasquatch',
    'name' => clienttranslate("Sasquatch Mating Season"),
    'tooltip' => clienttranslate("Player loses a turn. You may take over this player's tree and discard your own.")
  ],
  'plus_minus_axe_slip' => [
    'id' => 26,
    'type' => 'plus_minus',
    'name' => clienttranslate("Axe Slip"),
    'tooltip' => clienttranslate("Subtract one die from your next chopping roll.")
  ],
  'equipment_boots' => [
    'id' => 27,
    'type' => 'equipment',
    'name' => clienttranslate("Boots"),
    'tooltip' => clienttranslate("Prevents foot slip")
  ],
  'action_steal_axe' => [
    'id' => 28,
    'type' => 'action',
    'name' => clienttranslate("Steal Axe"),
    'tooltip' => clienttranslate("Take one axe card in play from any player.")
  ],
  'action_switch_tags' => [
    'id' => 29,
    'type' => 'action',
    'name' => clienttranslate("Switch Tags"),
    'tooltip' => clienttranslate("Exchange a chopped down tree with any player.")
  ],
  'action_tree_hugger' => [
    'id' => 30,
    'type' => 'action',
    'name' => clienttranslate("Tree Hugger"),
    'tooltip' => clienttranslate("Player loses a turn.")
  ],
  'contest_axe_throw' => [
    'id' => 31,
    'type' => 'contest',
    'name' => clienttranslate("Axe Throw"),
    'tooltip' => clienttranslate("Choose an opponent:</br>Both players roll one die. The higher roll gets +2 dice on next chopping roll.")
  ],
  'contest_equipment_chainsaw_carving' => [
    'id' => 32,
    'type' => 'contest_equipment',
    'name' => clienttranslate("Chainsaw Carving"),
    'tooltip' => clienttranslate("Choose an opponent:</br>Both players roll one die. The higher roll wins this chainsaw.")
  ],
  'reaction_debunk' => [
    'id' => 33,
    'type' => 'reaction',
    'name' => clienttranslate("Debunk"),
    'tooltip' => clienttranslate("Stops any Sasquatch card immediately.")
  ],
  'plus_minus_flapjacks' => [
    'id' => 34,
    'type' => 'plus_minus',
    'name' => clienttranslate("Flapjacks"),
    'tooltip' => clienttranslate("Add two dice to your chopping roll this turn.")
  ],
  'action_forest_fire' => [
    'id' => 35,
    'type' => 'action',
    'name' => clienttranslate("Forest Fire"),
    'tooltip' => clienttranslate("All trees in play are destroyed.")
  ],
  'help_long_saw_and_partner' => [
    'id' => 36,
    'type' => 'help',
    'name' => clienttranslate("Long Saw and Partner"),
    'tooltip' => clienttranslate("Set your axe aside and roll 5 dice.</br>If on any turn you roll 4 misses or breaks, move this card to the player on your right.")
  ],
  'reaction_paperwork' => [
    'id' => 37,
    'type' => 'reaction',
    'name' => clienttranslate("Paperwork"),
    'tooltip' => clienttranslate("Prevents Switch Tags and Tree Hugger immediately.")
  ],
  'sasquatch_sasquatch_sighting' => [
    'id' => 38,
    'type' => 'sasquatch',
    'name' => clienttranslate("Sasquatch Sighting"),
    'tooltip' => clienttranslate("All opponents roll one die. Players that roll 1, 2, or 3 lose their next turn.")
  ],
  'plus_minus_shortstack' => [
    'id' => 39,
    'type' => 'plus_minus',
    'name' => clienttranslate("Shortstack"),
    'tooltip' => clienttranslate("Add one die to your chopping roll this turn.")
  ],
  'reaction_northern_justice' => [
    'id' => 40,
    'type' => 'reaction',
    'name' => clienttranslate("Northern Justice"),
    'tooltip' => clienttranslate("Prevents Steal Axe and Steal Equipment immediately.</br>The player caught stealing loses chopping roll this turn.")
  ]
 ];



