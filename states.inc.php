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

$machinestates = [
    // Start of player turn - check if player needs a tree
    10 => [
        "name" => "playerTurnStart",
        "description" => "",
        "type" => "game",
        "action" => "stPlayerTurnStart",
        "transitions" => [
            "drawTree" => 11,
            "drawCard" => 20,
        ],
    ],

    // Player draws a tree card
    11 => [
        "name" => "drawTreeCard",
        "description" => clienttranslate('${actplayer} draws a tree card'),
        "descriptionmyturn" => clienttranslate('${you} draw a tree card'),
        "type" => "game",
        "action" => "stDrawTreeCard",
        "transitions" => ["next" => 20],
    ],

    // Player draws a card from Jack Deck
    20 => [
        "name" => "drawJackCard",
        "description" => clienttranslate('${actplayer} draws a card'),
        "descriptionmyturn" => clienttranslate('${you} draw a card'),
        "type" => "game",
        "action" => "stDrawJackCard",
        "transitions" => ["next" => 30],
    ],

    // Player must play a card or discard
    30 => [
        "name" => "playerTurn",
        "description" => clienttranslate(
            '${actplayer} must play a card or discard',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} must play a card or discard',
        ),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => ["actPlayCard", "actDiscardCard"],
        "transitions" => [
            "selectTarget" => 31,
            "checkReaction" => 32,
            "cardDiscarded" => 50,
        ],
    ],

    // Player selects target for card (if needed) - BEFORE reactions
    31 => [
        "name" => "selectTarget",
        "description" => clienttranslate(
            '${actplayer} must select a target for ${card_name}',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} must select a target for ${card_name}',
        ),
        "type" => "activeplayer",
        "args" => "argSelectTarget",
        "possibleactions" => ["actSelectTarget"],
        "transitions" => ["next" => 32],
    ],

    // Check if anyone wants to react to the played card
    32 => [
        "name" => "checkReaction",
        "description" => "",
        "type" => "game",
        "action" => "stCheckReaction",
        "transitions" => [
            "reaction" => 33,
            "noReaction" => 40,
            "selectTreesToSwitch" => 35,
        ],
    ],

    // Multiple players can react with Debunk/Paperwork/Northern Justice
    33 => [
        "name" => "reactionWindow",
        "description" => clienttranslate(
            '${actplayer_name} plays ${card_name}${target_desc}. Other players may react',
        ),
        "descriptionmyturn" => clienttranslate(
            '${actplayer_name} plays ${card_name}${target_desc}. ${you} may play a reaction card',
        ),
        "type" => "multipleactiveplayer",
        "args" => "argReactionWindow",
        "possibleactions" => ["actPlayReaction", "actPassReaction"],
        "transitions" => ["next" => 34],
    ],

    // Resolve reactions
    34 => [
        "name" => "resolveReaction",
        "description" => "",
        "type" => "game",
        "action" => "stResolveReaction",
        "transitions" => [
            "blocked" => 50, // Card was blocked, skip to chopping roll
            "proceed" => 40, // No block, proceed with card effect
            "selectTreesToSwitch" => 35, // No block
        ],
    ],

    // Validate both players have cut trees before entering selection
    35 => [
        "name" => "checkSwitchTagsTrees",
        "description" => "",
        "type" => "game",
        "action" => "stCheckSwitchTagsTrees",
        "transitions" => [
            "select" => 36,
            "noTrees" => 30, // Return to card selection
        ],
    ],

    // Player selects targets for switch tags
    36 => [
        "name" => "selectTreesToSwitch",
        "description" => clienttranslate(
            '${actplayer} must select which cut trees to switch for ${card_name}',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} must select a tree from your cut pile to switch with a tree from your target\'s cut pile for ${card_name}',
        ),
        "type" => "activeplayer",
        "args" => "argSelectTreesToSwitch",
        "possibleactions" => ["actSelectTreesToSwitch"],
        "transitions" => ["next" => 40],
    ],

    // Resolve the card effect
    40 => [
        "name" => "resolveCard",
        "description" => "",
        "type" => "game",
        "action" => "stResolveCard",
        "transitions" => [
            "sasquatchSighting" => 41,
            "chooseTakeTree" => 43,
            "contest" => 42,
            "selectTreesToSwitch" => 35,
            "next" => 50,
        ],
    ],

    // Sasquatch Sighting - all opponents roll save
    41 => [
        "name" => "sasquatchSighting",
        "description" => clienttranslate(
            "All players must roll to avoid the Sasquatch",
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} must roll to avoid the Sasquatch',
        ),
        "type" => "multipleactiveplayer",
        "action" => "stSasquatchSighting",
        "possibleactions" => ["actRollSave"],
        "transitions" => ["next" => 50],
    ],

    // Sasquatch Mating Season - active player chooses whether to take target's tree
    43 => [
        "name" => "chooseTakeTree",
        "description" => clienttranslate(
            '${actplayer} may take ${target_name}\'s tree',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} may take ${target_name}\'s tree',
        ),
        "type" => "activeplayer",
        "args" => "argChooseTakeTree",
        "possibleactions" => ["actChooseTakeTree"],
        "transitions" => ["next" => 50],
    ],

    // Contest roll (both players roll)
    42 => [
        "name" => "contestRoll",
        "description" => clienttranslate(
            '${actplayer} and ${target_name} compete',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} compete with ${target_name}',
        ),
        "type" => "game",
        "action" => "stContestRoll",
        "transitions" => ["next" => 50],
    ],

    // Pre-check: does player have a chopping tool?
    50 => [
        "name" => "preChoppingRoll",
        "description" => "",
        "type" => "game",
        "action" => "stPreChoppingRoll",
        "transitions" => ["roll" => 51, "skip" => 60],
    ],

    // Player clicks to roll dice
    51 => [
        "name" => "choppingRoll",
        "description" => clienttranslate(
            '${actplayer} must perform their chopping roll',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you} must perform your chopping roll',
        ),
        "type" => "activeplayer",
        "args" => "argChoppingRoll",
        "possibleactions" => ["actChopRoll"],
        "transitions" => ["next" => 52],
    ],

    // Player reviews results and clicks continue
    52 => [
        "name" => "choppingRollResult",
        "description" => clienttranslate(
            '${actplayer} reviews chopping roll results',
        ),
        "descriptionmyturn" => clienttranslate(
            '${you}: review your chopping roll results',
        ),
        "type" => "activeplayer",
        "args" => "argChoppingRollResult",
        "possibleactions" => ["actConfirmChopResult"],
        "transitions" => ["next" => 60],
    ],

    // Check if tree is complete and if game is won
    60 => [
        "name" => "checkTreeComplete",
        "description" => "",
        "type" => "game",
        "action" => "stCheckTreeComplete",
        "updateGameProgression" => true,
        "transitions" => [
            "treeComplete" => 61,
            "continue" => 70,
        ],
    ],

    // Tree completed - move to cut pile
    61 => [
        "name" => "treeCompleted",
        "description" => "",
        "type" => "game",
        "action" => "stTreeCompleted",
        "transitions" => [
            "gameEnd" => 99,
            "continue" => 70,
        ],
    ],

    // Next player
    70 => [
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => [
            "nextTurn" => 10,
            "skipTurn" => 70,
        ],
    ],
];
