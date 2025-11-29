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
 * states.inc.php
 *
 * FlapjacksAndSasquatches game states description
 *
 */

/*
   Game state machine for Flapjacks and Sasquatches

   Turn Flow:
   1. Draw tree card (if player doesn't have one)
   2. Draw card from Jack Deck
   3. Play a card or discard
   4. Select target (if needed) - BEFORE reactions so players know who is targeted
   5. Check for reactions (multipleactiveplayer)
   6. Resolve card effect
   7. Perform chopping roll (if able)
   8. Check for tree completion
   9. Next player
*/

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array( "" => 10 )
    ),

    // Start of player turn - check if player needs a tree
    10 => array(
        "name" => "playerTurnStart",
        "description" => "",
        "type" => "game",
        "action" => "stPlayerTurnStart",
        "transitions" => array(
            "drawTree" => 11,
            "drawCard" => 20
        )
    ),

    // Player draws a tree card
    11 => array(
        "name" => "drawTreeCard",
        "description" => clienttranslate('${actplayer} draws a tree card'),
        "descriptionmyturn" => clienttranslate('${you} draw a tree card'),
        "type" => "game",
        "action" => "stDrawTreeCard",
        "transitions" => array( "next" => 20 )
    ),

    // Player draws a card from Jack Deck
    20 => array(
        "name" => "drawJackCard",
        "description" => clienttranslate('${actplayer} draws a card'),
        "descriptionmyturn" => clienttranslate('${you} draw a card'),
        "type" => "game",
        "action" => "stDrawJackCard",
        "transitions" => array( "next" => 30 )
    ),

    // Player must play a card or discard
    30 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card or discard'),
        "descriptionmyturn" => clienttranslate('${you} must play a card or discard'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => array( "playCard", "discardCard" ),
        "transitions" => array(
            "cardPlayed" => 31,
            "cardDiscarded" => 50
        )
    ),

    // Player selects target for card (if needed) - BEFORE reactions
    31 => array(
        "name" => "selectTarget",
        "description" => clienttranslate('${actplayer} must select a target for ${card_name}'),
        "descriptionmyturn" => clienttranslate('${you} must select a target for ${card_name}'),
        "type" => "activeplayer",
        "args" => "argSelectTarget",
        "possibleactions" => array( "selectTarget" ),
        "transitions" => array( "next" => 32 )
    ),

    // Check if anyone wants to react to the played card
    32 => array(
        "name" => "checkReaction",
        "description" => "",
        "type" => "game",
        "action" => "stCheckReaction",
        "transitions" => array(
            "reaction" => 33,
            "noReaction" => 40
        )
    ),

    // Multiple players can react with Debunk/Paperwork/Northern Justice
    33 => array(
        "name" => "reactionWindow",
        "description" => clienttranslate('Other players may react to ${card_name} on ${target_name}'),
        "descriptionmyturn" => clienttranslate('${you} may play a reaction card'),
        "type" => "multipleactiveplayer",
        "args" => "argReactionWindow",
        "possibleactions" => array( "playReaction", "passReaction" ),
        "transitions" => array( "next" => 34 )
    ),

    // Resolve reactions
    34 => array(
        "name" => "resolveReaction",
        "description" => "",
        "type" => "game",
        "action" => "stResolveReaction",
        "transitions" => array(
            "blocked" => 50,      // Card was blocked, skip to chopping roll
            "proceed" => 40       // No block, proceed with card effect
        )
    ),

    // Resolve the card effect
    40 => array(
        "name" => "resolveCard",
        "description" => "",
        "type" => "game",
        "action" => "stResolveCard",
        "transitions" => array(
            "sasquatchSighting" => 41,
            "contest" => 42,
            "next" => 50
        )
    ),

    // Sasquatch Sighting - all opponents roll save
    41 => array(
        "name" => "sasquatchSighting",
        "description" => clienttranslate('All players must roll to avoid the Sasquatch'),
        "descriptionmyturn" => clienttranslate('${you} must roll to avoid the Sasquatch'),
        "type" => "multipleactiveplayer",
        "action" => "stSasquatchSighting",
        "possibleactions" => array( "rollSave" ),
        "transitions" => array( "next" => 50 )
    ),

    // Contest roll (both players roll)
    42 => array(
        "name" => "contestRoll",
        "description" => clienttranslate('${actplayer} and ${target_name} compete'),
        "descriptionmyturn" => clienttranslate('${you} compete with ${target_name}'),
        "type" => "game",
        "action" => "stContestRoll",
        "transitions" => array( "next" => 50 )
    ),

    // Perform chopping roll
    50 => array(
        "name" => "choppingRoll",
        "description" => clienttranslate('${actplayer} performs chopping roll'),
        "descriptionmyturn" => clienttranslate('${you} perform your chopping roll'),
        "type" => "game",
        "action" => "stChoppingRoll",
        "transitions" => array( "next" => 60 )
    ),

    // Check if tree is complete and if game is won
    60 => array(
        "name" => "checkTreeComplete",
        "description" => "",
        "type" => "game",
        "action" => "stCheckTreeComplete",
        "updateGameProgression" => true,
        "transitions" => array(
            "treeComplete" => 61,
            "continue" => 70
        )
    ),

    // Tree completed - move to cut pile
    61 => array(
        "name" => "treeCompleted",
        "description" => "",
        "type" => "game",
        "action" => "stTreeCompleted",
        "transitions" => array(
            "gameEnd" => 99,
            "continue" => 70
        )
    ),

    // Next player
    70 => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => array(
            "nextTurn" => 10,
            "skipTurn" => 70
        )
    ),

    // Final state.
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
