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
 * modules/php/Game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to define the rules of the game.
 *
 */

namespace Bga\Games\flapjacksandsasquatches;

class Game extends \Bga\GameFramework\Table
{
    private $jack_cards;
    private $tree_cards;
    private $card_backs;
    private $game_constants;
    private $cards;

    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        require "material.inc.php";

        self::initGameStateLabels([
            "current_round" => 10,
            "reaction_pending_card" => 11,
            "reaction_pending_player" => 12,
        ]);

        // Create deck manager
        $this->cards = $this->deckFactory->createDeck("card");
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos["player_colors"];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql =
            "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] =
                "('" .
                $player_id .
                "','$color','" .
                $player["player_canal"] .
                "','" .
                addslashes($player["player_name"]) .
                "','" .
                addslashes($player["player_avatar"]) .
                "')";
        }
        $sql .= implode(",", $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences(
            $players,
            $gameinfos["player_colors"],
        );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue("current_round", 0);
        self::setGameStateInitialValue("reaction_pending_card", 0);
        self::setGameStateInitialValue("reaction_pending_player", 0);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat("table", "rounds_played", 0);
        self::initStat("table", "total_trees_chopped", 0);

        self::initStat("player", "trees_chopped", 0);
        self::initStat("player", "chops_rolled", 0);
        self::initStat("player", "axes_broken", 0);
        self::initStat("player", "sasquatch_cards_played", 0);
        self::initStat("player", "reaction_cards_played", 0);

        // Setup the card decks
        $this->setupCardDecks();

        // Deal initial hands
        $players_info = self::loadPlayersBasicInfos();
        foreach ($players_info as $player_id => $player) {
            // Deal 3 cards to each player
            $cards = $this->cards->pickCards(
                $this->game_constants["starting_hand_size"],
                "deck",
                $player_id,
            );
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/

        // Return initial state (state 10: playerTurnStart)
        return 10;
    }

    /*
        getAllDatas:

        Gather all informations about current game situation (visible by the current player).

        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = [];

        // Card definitions for client
        $result["jackCards"] = $this->jack_cards;
        $result["treeCards"] = $this->tree_cards;
        $result["cardBacks"] = $this->card_backs;
        $result["constants"] = $this->game_constants;

        $current_player_id = self::getCurrentPlayerId(); // !! We must only return informations visible by this player !!

        // Get information about players
        $sql = "SELECT player_id id, player_score score, player_score_aux scoreAux,
                       player_skip_next_turn skipNextTurn, player_current_tree_id currentTreeId
                FROM player ";
        $result["players"] = self::getCollectionFromDb($sql);

        // Get current player's hand (jack cards only)
        $result["hand"] = array_filter(
            $this->cards->getCardsInLocation("hand", $current_player_id),
            function ($card) {
                return $card["type"] === "jack";
            },
        );

        // Get deck counts
        $result["deckCount"] = $this->cards->countCardInLocation("deck");
        $result["treeDeckCount"] = $this->cards->countCardInLocation(
            "treedeck",
        );
        $result["discardCount"] = $this->cards->countCardInLocation("discard");

        // Get all players' equipment (visible to all)
        $sql = "SELECT pe.player_id, pe.card_id, pe.equipment_type, c.card_type_arg
                FROM player_equipment pe
                JOIN card c ON pe.card_id = c.card_id";
        $result["equipment"] = self::getObjectListFromDB($sql);

        // Get all players' help cards (visible to all)
        $sql = "SELECT ph.player_id, ph.card_id, ph.help_type, c.card_type_arg
                FROM player_help ph
                JOIN card c ON ph.card_id = c.card_id";
        $result["helpCards"] = self::getObjectListFromDB($sql);

        // Get all players' modifiers (visible to all)
        $sql = "SELECT pm.player_id, pm.card_id, pm.modifier_type, pm.modifier_value,
                       pm.is_persistent, c.card_type_arg
                FROM player_modifiers pm
                JOIN card c ON pm.card_id = c.card_id";
        $result["modifiers"] = self::getObjectListFromDB($sql);

        // Get all players' active trees (visible to all)
        $sql = "SELECT at.tree_id, at.player_id, at.card_id, at.tree_type,
                       at.chop_count, at.chops_required, at.points_value,
                       c.card_type_arg
                FROM active_tree at
                JOIN card c ON at.card_id = c.card_id";
        $result["activeTrees"] = self::getObjectListFromDB($sql);

        // Get all players' cut trees (visible to all)
        $sql = "SELECT ct.cut_tree_id, ct.player_id, ct.card_id, ct.tree_type,
                       ct.points_value, ct.cut_order, c.card_type_arg
                FROM cut_tree ct
                JOIN card c ON ct.card_id = c.card_id
                ORDER BY ct.cut_order ASC";
        $result["cutTrees"] = self::getObjectListFromDB($sql);

        return $result;
    }

    /*
        getGameProgression:

        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).

        This method is called each time we are in a game state with the "updateGameProgression" property set to true
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // Game progression based on highest player score vs winning score
        $sql = "SELECT MAX(player_score) as max_score FROM player";
        $max_score = self::getUniqueValueFromDB($sql);

        $winning_score = $this->game_constants["winning_score"];

        // Return percentage (0-100)
        $progression = min(100, ($max_score / $winning_score) * 100);

        return $progression;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    /**
     * Setup card decks - creates and shuffles both Jack Deck and Tree Deck
     */
    function setupCardDecks()
    {
        $deck_cards = [];

        // Create Jack Deck (red cards)
        foreach ($this->jack_cards as $card_key => $card_def) {
            if (isset($card_def["qty"]) && $card_def["qty"] > 0) {
                $deck_cards[] = [
                    "type" => "jack",
                    "type_arg" => $card_def["id"],
                    "nbr" => $card_def["qty"],
                ];
            }
        }

        // Create Tree Deck
        foreach ($this->tree_cards as $tree_key => $tree_def) {
            $deck_cards[] = [
                "type" => "tree",
                "type_arg" => $tree_def["id"],
                "nbr" => $tree_def["qty"],
            ];
        }

        // Create all cards
        $this->cards->createCards($deck_cards, "deck");

        // Separate tree cards into tree deck
        $tree_cards = $this->cards->getCardsOfType("tree");
        foreach ($tree_cards as $card_id => $card) {
            $this->cards->moveCard($card_id, "treedeck");
        }

        // Shuffle both decks
        $this->cards->shuffle("deck"); // Jack Deck
        $this->cards->shuffle("treedeck"); // Tree Deck
    }

    /**
     * Get card definition by ID
     */
    function getCardDefinitionForCardId($card_id)
    {
        return $this->getCardDefinitionForCard($this->cards->getCard($card_id));
    }

    /**
     * Get card definition by ID
     */
    function getCardDefinitionForCard($card)
    {
        if (!isset($card)) {
            throw new BgaVisibleSystemException("Card not provided");
        }
        $card_type = $card["type"];
        $card_def_id = $card["type_arg"];

        $definition = null;

        switch ($card_type) {
            case "jack":
                $definition = $this->jack_cards[$card_def_id];
                break;
            case "tree":
                $definition = $this->tree_cards[$card_def_id];
                break;
            default:
                throw new BgaVisibleSystemException(
                    "Card type $card_type not found",
                );
        }

        if (!isset($definition)) {
            throw new BgaVisibleSystemException(
                "Card definition not found for type: $card_type and id: $card_def_id",
            );
        }

        return $definition;
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    ////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        These methods use auto-wired actions (no need for .action.php file)
    */

    public function actPlayCard(int $card_id)
    {
        // Auto-wired actions automatically call checkAction
        $player_id = self::getActivePlayerId();

        // Get card info
        $card = $this->cards->getCard($card_id);
        if (
            !$card ||
            $card["location"] != "hand" ||
            $card["location_arg"] != $player_id
        ) {
            throw new BgaUserException(
                clienttranslate("This card is not in your hand"),
            );
        }

        $card_def = $this->getCardDefinitionForCard($card);

        // Move card to limbo (pending state until resolution)
        $this->cards->moveCard($card_id, "limbo");

        // Store pending card for reaction/resolution
        self::setGameStateValue("reaction_pending_card", $card_id);

        // Notify card played
        self::notifyAllPlayers(
            "cardPlayed",
            clienttranslate('${player_name} plays ${card_name}'),
            [
                "player_id" => $player_id,
                "player_name" => self::getActivePlayerName(),
                "card_name" => $card_def["name"],
                "card_id" => $card_id,
                "card_type" => $card_def["type"],
            ],
        );

        // Route based on targeting mode
        $targeted = $card_def["targeted"] ?? false;
        if ($targeted === "self") {
            // Auto-target the active player, skip target selection
            self::setGameStateValue("reaction_pending_player", $player_id);
            $this->gamestate->nextState("checkReaction");
        } elseif ($targeted === "others" || $targeted === "any") {
            // Switch Tags: active player must also have cut trees to swap
            if ($card_def["id"] == 20) {
                $my_cut_tree = self::getObjectFromDB(
                    "SELECT cut_tree_id FROM cut_tree
                     WHERE player_id = $player_id LIMIT 1",
                );
                if (!$my_cut_tree) {
                    $this->cards->moveCard($card_id, "hand", $player_id);
                    self::notifyAllPlayers(
                        "cardReturned",
                        clienttranslate(
                            '${card_name} has no valid targets and is returned to ${player_name}\'s hand',
                        ),
                        [
                            "player_id" => $player_id,
                            "player_name" => self::getActivePlayerName(),
                            "card_name" => $card_def["name"],
                            "card_id" => $card_id,
                            "card_type_arg" => $card_def["id"],
                        ],
                    );
                    return;
                }
            }

            // Check if there are valid targets before entering selection
            $valid_targets = $this->getValidTargets($card_def, $player_id);
            if (count($valid_targets) === 0) {
                // No valid targets — return card to hand
                $this->cards->moveCard($card_id, "hand", $player_id);
                self::notifyAllPlayers(
                    "cardReturned",
                    clienttranslate(
                        '${card_name} has no valid targets and is returned to ${player_name}\'s hand',
                    ),
                    [
                        "player_id" => $player_id,
                        "player_name" => self::getActivePlayerName(),
                        "card_name" => $card_def["name"],
                        "card_id" => $card_id,
                        "card_type_arg" => $card_def["id"],
                    ],
                );
                // Stay in playerTurn — do not transition
                return;
            }
            $this->gamestate->nextState("selectTarget");
        } else {
            $this->gamestate->nextState("checkReaction");
        }
    }

    public function actDiscardCard(int $card_id)
    {
        // Auto-wired actions automatically call checkAction
        $player_id = self::getActivePlayerId();

        // Get card info
        $card = $this->cards->getCard($card_id);
        if (
            !$card ||
            $card["location"] != "hand" ||
            $card["location_arg"] != $player_id
        ) {
            throw new BgaUserException(
                clienttranslate("This card is not in your hand"),
            );
        }

        // Move to discard
        $this->cards->moveCard($card_id, "discard");

        // Notify discard
        self::notifyAllPlayers(
            "cardDiscarded",
            clienttranslate('${player_name} discards a card'),
            [
                "player_id" => $player_id,
                "player_name" => self::getActivePlayerName(),
                "card_id" => $card_id,
            ],
        );

        // Move to chopping roll
        $this->gamestate->nextState("cardDiscarded");
    }

    function actSelectTarget(int $target_id)
    {
        self::checkAction("actSelectTarget");
        $player_id = self::getActivePlayerId();

        // Validate target exists
        $players = self::loadPlayersBasicInfos();
        if (!isset($players[$target_id])) {
            throw new BgaUserException(
                clienttranslate("Invalid target player"),
            );
        }

        // Validate targeting mode
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $card_def = $this->getCardDefinitionForCardId($pending_card_id);
        $targeted = $card_def["targeted"] ?? false;

        if ($targeted === "others" && $target_id == $player_id) {
            throw new BgaUserException(
                clienttranslate("You cannot target yourself with this card"),
            );
        }

        // Validate target is not protected by preventing equipment
        $valid_targets = $this->getValidTargets($card_def, $player_id);
        if (!in_array($target_id, $valid_targets)) {
            throw new BgaUserException(
                clienttranslate(
                    "This player's equipment prevents this card from affecting them",
                ),
            );
        }

        // Store target
        self::setGameStateValue("reaction_pending_player", $target_id);

        // Notify target selected
        self::notifyAllPlayers(
            "targetSelected",
            clienttranslate('${player_name} targets ${target_name}'),
            [
                "player_id" => $player_id,
                "player_name" => self::getActivePlayerName(),
                "target_id" => $target_id,
                "target_name" => self::getPlayerNameById($target_id),
            ],
        );

        // Move to reaction check
        $this->gamestate->nextState("next");
    }

    function actSelectTreesToSwitch(
        int $my_cut_tree_id,
        int $target_cut_tree_id,
    ) {
        self::checkAction("actSelectTreesToSwitch");

        $player_id = self::getActivePlayerId();
        $target_id = self::getGameStateValue("reaction_pending_player");

        // Validate ownership
        $my_tree = self::getObjectFromDB(
            "SELECT cut_tree_id FROM cut_tree
             WHERE cut_tree_id = " .
                intval($my_cut_tree_id) .
                "
             AND player_id = $player_id",
        );
        $target_tree = self::getObjectFromDB(
            "SELECT cut_tree_id FROM cut_tree
             WHERE cut_tree_id = " .
                intval($target_cut_tree_id) .
                "
             AND player_id = $target_id",
        );

        if (!$my_tree || !$target_tree) {
            throw new BgaUserException(
                clienttranslate("Invalid tree selection"),
            );
        }

        // Store selections in globals for resolveCard to use
        $this->globals->set("switch_tags_my_tree", intval($my_cut_tree_id));
        $this->globals->set(
            "switch_tags_target_tree",
            intval($target_cut_tree_id),
        );

        $this->gamestate->nextState("next");
    }

    function actPlayReaction(int $card_id)
    {
        self::checkAction("actPlayReaction");
        $player_id = self::getCurrentPlayerId();

        // Get card info
        $card = $this->cards->getCard($card_id);
        if (
            !$card ||
            $card["location"] != "hand" ||
            $card["location_arg"] != $player_id
        ) {
            throw new BgaUserException(
                clienttranslate("This card is not in your hand"),
            );
        }

        $card_def = $this->getCardDefinitionForCard($card);

        // Validate it's a reaction card
        if ($card_def["type"] !== "reaction") {
            throw new BgaUserException(
                clienttranslate("This card cannot be played as a reaction"),
            );
        }

        // Move to discard (reactions are discarded after use)
        $this->cards->moveCard($card_id, "discard");

        // Mark that a reaction was played
        $this->globals->set("reaction_played", true);

        // Reaction cards get a replacement draw immediately
        $new_card = $this->cards->pickCard("deck", $player_id);
        if ($new_card) {
            $new_card_def = $this->getCardDefinitionForCard($new_card);
            self::notifyPlayer(
                $player_id,
                "cardDrawn",
                clienttranslate(
                    'You draw ${card_name} to replace your reaction card',
                ),
                [
                    "card" => $new_card,
                    "card_name" => $new_card_def["name"],
                ],
            );
        }

        // Deactivate this player
        $this->gamestate->setPlayerNonMultiactive($player_id, "next");

        // Notify reaction played
        self::notifyAllPlayers(
            "reactionPlayed",
            clienttranslate('${player_name} plays ${card_name} as a reaction!'),
            [
                "player_id" => $player_id,
                "player_name" => self::getPlayerNameById($player_id),
                "card_name" => $card_def["name"],
                "card_id" => $card_id,
                "card_type" => $card_def["type"],
            ],
        );
    }

    function actPassReaction()
    {
        self::checkAction("actPassReaction");
        $player_id = self::getCurrentPlayerId();

        // Deactivate this player
        $this->gamestate->setPlayerNonMultiactive($player_id, "next");

        // Notify pass
        self::notifyAllPlayers(
            "reactionPassed",
            clienttranslate('${player_name} passes'),
            [
                "player_id" => $player_id,
                "player_name" => self::getPlayerNameById($player_id),
            ],
        );
    }

    /**
     * Action: actRollSave
     * Opponent rolls one die during Sasquatch Sighting.
     * Rolls 1-3 = lose next turn, rolls 4-6 = safe.
     */
    function actRollSave()
    {
        self::checkAction("actRollSave");
        $player_id = self::getCurrentPlayerId();

        // Roll one die
        $roll = bga_rand(1, 6);
        $loses_turn = $roll <= 3;

        if ($loses_turn) {
            self::DbQuery(
                "UPDATE player SET player_skip_next_turn = 1
                 WHERE player_id = $player_id",
            );
        }

        self::notifyAllPlayers(
            "sasquatchSightingRoll",
            $loses_turn
                ? clienttranslate(
                    '${player_name} rolls ${roll_result} and loses their next turn!',
                )
                : clienttranslate(
                    '${player_name} rolls ${roll_result} and is safe!',
                ),
            [
                "player_id" => $player_id,
                "player_name" => self::getPlayerNameById($player_id),
                "roll_result" => $roll,
                "loses_turn" => $loses_turn,
            ],
        );

        // Deactivate this player
        $this->gamestate->setPlayerNonMultiactive($player_id, "next");
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    function argPlayerTurn()
    {
        $player_id = self::getActivePlayerId();

        // Get player's hand
        $hand = $this->cards->getCardsInLocation("hand", $player_id);

        return [
            "canPlay" => count($hand) > 0,
        ];
    }

    function argReactionWindow()
    {
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $target_player_id = self::getGameStateValue("reaction_pending_player");
        $active_player_id = self::getActivePlayerId();
        $card_def = $this->getCardDefinitionForCardId($pending_card_id);

        $target_name = $target_player_id
            ? self::getPlayerNameById($target_player_id)
            : "";

        return [
            "card_name" => $card_def["name"],
            "card_id" => $pending_card_id,
            "card_type" => $card_def["type"],
            "actplayer_name" => self::getPlayerNameById($active_player_id),
            "target_name" => $target_name,
            "target_desc" => $target_name ? " on ${target_name}" : "",
            "target_id" => $target_player_id,
        ];
    }

    function argSelectTarget()
    {
        $player_id = self::getActivePlayerId();
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $card_def = $this->getCardDefinitionForCardId($pending_card_id);
        $targeted = $card_def["targeted"] ?? false;

        $valid_targets = $this->getValidTargets($card_def, $player_id);

        return [
            "card_name" => $card_def["name"],
            "targeted" => $targeted,
            "valid_targets" => $valid_targets,
        ];
    }

    /**
     * Get valid target player IDs for a card, filtering out players
     * who have equipment that prevents the card's effect.
     */
    function getValidTargets($card_def, $player_id)
    {
        $targeted = $card_def["targeted"] ?? false;
        $players = self::loadPlayersBasicInfos();
        $valid_targets = [];

        $prevents_card_def_id = $card_def["preventable_by"] ?? null;

        foreach ($players as $pid => $player) {
            if ($targeted === "others" && $pid == $player_id) {
                continue;
            }

            // Skip players who have equipment that prevents this card
            if ($prevents_card_def_id !== null) {
                $has_prevention = self::getObjectFromDB(
                    "SELECT pe.equipment_id FROM player_equipment pe
                     JOIN card c ON pe.card_id = c.card_id
                     WHERE pe.player_id = $pid
                     AND c.card_type_arg = $prevents_card_def_id
                     AND c.card_type = 'jack'",
                );
                if ($has_prevention) {
                    continue;
                }
            }

            // Axe Break: target must have a breakable axe
            if ($card_def["id"] == 8) {
                $axe = self::getObjectFromDB(
                    "SELECT pe.equipment_id, c.card_type_arg
                     FROM player_equipment pe
                     JOIN card c ON pe.card_id = c.card_id
                     WHERE pe.player_id = $pid AND pe.equipment_type = 'axe'",
                );
                if (!$axe) {
                    continue;
                }
                $axe_def = $this->jack_cards[intval($axe["card_type_arg"])];
                if (isset($axe_def["unbreakable"]) && $axe_def["unbreakable"]) {
                    continue;
                }
            }

            // Switch Tags: target must have at least one cut tree
            if ($card_def["id"] == 20) {
                $has_cut_tree = self::getObjectFromDB(
                    "SELECT cut_tree_id FROM cut_tree
                     WHERE player_id = $pid LIMIT 1",
                );
                if (!$has_cut_tree) {
                    continue;
                }
            }

            $valid_targets[] = $pid;
        }

        return $valid_targets;
    }

    function argSelectTreesToSwitch()
    {
        $player_id = self::getActivePlayerId();
        $target_id = self::getGameStateValue("reaction_pending_player");
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $card_def = $this->getCardDefinitionForCardId($pending_card_id);

        $my_trees = self::getObjectListFromDB(
            "SELECT ct.cut_tree_id, ct.card_id, ct.tree_type, ct.points_value, c.card_type_arg
             FROM cut_tree ct JOIN card c ON ct.card_id = c.card_id
             WHERE ct.player_id = $player_id",
        );
        $target_trees = self::getObjectListFromDB(
            "SELECT ct.cut_tree_id, ct.card_id, ct.tree_type, ct.points_value, c.card_type_arg
             FROM cut_tree ct JOIN card c ON ct.card_id = c.card_id
             WHERE ct.player_id = $target_id",
        );

        return [
            "card_name" => $card_def["name"],
            "my_trees" => $my_trees,
            "target_trees" => $target_trees,
            "target_id" => $target_id,
        ];
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /**
     * State: playerTurnStart
     * Check if player needs a tree card
     */
    function stPlayerTurnStart()
    {
        $player_id = self::getActivePlayerId();

        // Check if player has an active tree
        $sql = "SELECT tree_id FROM active_tree WHERE player_id = $player_id";
        $has_tree = self::getUniqueValueFromDB($sql);

        if (!$has_tree) {
            $this->gamestate->nextState("drawTree");
        } else {
            $this->gamestate->nextState("drawCard");
        }
    }

    /**
     * State: drawTreeCard
     * Player draws a tree card automatically
     */
    function stDrawTreeCard()
    {
        $player_id = self::getActivePlayerId();

        // Draw a tree card
        $tree_card = $this->cards->pickCard("treedeck", $player_id);

        if ($tree_card) {
            // Move tree card out of "hand" (pickCard defaults to "hand" location)
            $this->cards->moveCard($tree_card["id"], "treeactive", $player_id);

            // Get tree info
            $tree_def = $this->tree_cards[$tree_card["type_arg"]];

            // Create active tree entry
            $sql = "INSERT INTO active_tree (player_id, card_id, tree_type, chop_count, chops_required, points_value)
                    VALUES ($player_id, {$tree_card["id"]}, '{$tree_card["type"]}', 0, {$tree_def["chops_required"]}, {$tree_def["points"]})";
            self::DbQuery($sql);

            // Update player's current tree reference
            $tree_id = self::getUniqueValueFromDB("SELECT LAST_INSERT_ID()");
            $sql = "UPDATE player SET player_current_tree_id = $tree_id WHERE player_id = $player_id";
            self::DbQuery($sql);

            // Notify all players
            self::notifyAllPlayers(
                "treeDrawn",
                clienttranslate('${player_name} draws ${tree_name}'),
                [
                    "player_id" => $player_id,
                    "player_name" => self::getActivePlayerName(),
                    "tree_name" => $tree_def["name"],
                    "tree_id" => $tree_id,
                    "card_id" => $tree_card["id"],
                    "card_type_arg" => $tree_card["type_arg"],
                    "tree_type" => $tree_card["type"],
                    "chops_required" => $tree_def["chops_required"],
                    "points" => $tree_def["points"],
                ],
            );
        }

        $this->gamestate->nextState("next");
    }

    /**
     * State: drawJackCard
     * Player draws a card from Jack Deck
     */
    function stDrawJackCard()
    {
        $player_id = self::getActivePlayerId();

        // Draw a card
        $card = $this->cards->pickCard("deck", $player_id);

        if ($card) {
            $card_def = $this->getCardDefinitionForCard($card);

            // Notify player privately (send Deck card so JS gets id, type, type_arg)
            self::notifyPlayer($player_id, "cardDrawn", "", [
                "player_id" => $player_id,
                "card" => $card,
            ]);

            // Notify all players publicly (no card details)
            self::notifyAllPlayers(
                "cardDrawnPublic",
                clienttranslate('${player_name} draws a card'),
                [
                    "player_id" => $player_id,
                    "player_name" => self::getActivePlayerName(),
                ],
            );
        }

        $this->gamestate->nextState("next");
    }

    /**
     * State: checkSwitchTagsTrees
     * Validate both players have cut trees before entering tree selection.
     * Kicks back to selectTarget if either player's cut pile is empty.
     */
    function stCheckSwitchTagsTrees()
    {
        $player_id = self::getActivePlayerId();
        $target_id = self::getGameStateValue("reaction_pending_player");

        $my_tree = self::getObjectFromDB(
            "SELECT cut_tree_id FROM cut_tree
             WHERE player_id = $player_id LIMIT 1",
        );
        $their_tree = self::getObjectFromDB(
            "SELECT cut_tree_id FROM cut_tree
             WHERE player_id = $target_id LIMIT 1",
        );

        if (!$my_tree || !$their_tree) {
            $pending_card_id = self::getGameStateValue("reaction_pending_card");
            $card_def = $this->getCardDefinitionForCardId($pending_card_id);

            self::notifyAllPlayers(
                "cardReturned",
                clienttranslate(
                    '${card_name} has no valid targets and is returned to ${player_name}\'s hand',
                ),
                [
                    "player_id" => $player_id,
                    "player_name" => self::getPlayerNameById($player_id),
                    "card_name" => $card_def["name"],
                    "card_id" => $pending_card_id,
                    "card_type_arg" => $card_def["id"],
                ],
            );

            // Return card to hand
            $this->cards->moveCard($pending_card_id, "hand", $player_id);

            $this->gamestate->nextState("noTrees");
        } else {
            $this->gamestate->nextState("select");
        }
    }

    /**
     * State: checkReaction
     * Check if any players can react to the played card
     */
    function stCheckReaction()
    {
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $card_def = $this->getCardDefinitionForCardId($pending_card_id);

        $blockable_by = $card_def["blockable_by"] ?? null;
        $active_players = [];
        $playing_player_id = self::getActivePlayerId();

        if ($blockable_by !== null) {
            // Find players who have the reaction card (excluding the player who played it)
            $players = self::loadPlayersBasicInfos();
            foreach ($players as $player_id => $player) {
                if ($player_id == $playing_player_id) {
                    continue;
                }
                $hand = $this->cards->getCardsInLocation("hand", $player_id);
                foreach ($hand as $card) {
                    if (in_array($card["type_arg"], $blockable_by)) {
                        $active_players[] = $player_id;
                        break;
                    }
                }
            }
        }

        if (count($active_players) > 0) {
            // Set players as multiactive for reaction
            $this->gamestate->setPlayersMultiactive(
                $active_players,
                "next",
                true,
            );
            $this->gamestate->nextState("reaction");
        } else {
            // Switch Tags needs tree selection before resolving
            if ($card_def["type"] == "action" && $card_def["id"] == 20) {
                $this->gamestate->nextState("selectTreesToSwitch");
            } else {
                $this->gamestate->nextState("noReaction");
            }
        }
    }

    /**
     * State: resolveReaction
     * Process any reactions that were played
     */
    function stResolveReaction()
    {
        // Check if any reaction was played (stored in globals)
        $reaction_played = $this->globals->get("reaction_played", false);
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $card_def = $this->getCardDefinitionForCardId($pending_card_id);

        if ($reaction_played) {
            // Notify that the card was blocked
            $active_player_id = self::getActivePlayerId();

            self::notifyAllPlayers(
                "cardBlocked",
                clienttranslate(
                    '${player_name}\'s ${card_name} is blocked by a reaction!',
                ),
                [
                    "player_id" => $active_player_id,
                    "player_name" => self::getPlayerNameById($active_player_id),
                    "card_name" => $card_def["name"],
                ],
            );

            // Card was blocked, skip to chopping roll
            $this->gamestate->nextState("blocked");
        } elseif ($card_def["type"] == "action" && $card_def["id"] == 20) {
            $this->gamestate->nextState("selectTreesToSwitch");
        } else {
            $this->gamestate->nextState("proceed");
        }

        // Clear reaction tracking
        $this->globals->set("reaction_played", false);
    }

    /**
     * State: resolveCard
     * Execute the effect of the played card
     */
    function stResolveCard()
    {
        $pending_card_id = self::getGameStateValue("reaction_pending_card");
        $card = $this->cards->getCard($pending_card_id);
        $card_def = $this->getCardDefinitionForCard($card);
        $target_id = self::getGameStateValue("reaction_pending_player");
        $player_id = self::getActivePlayerId();

        // Route to appropriate handler based on card type
        switch ($card_def["type"]) {
            case "equipment":
                $this->resolveEquipment(
                    $pending_card_id,
                    $card_def,
                    $target_id,
                );
                break;

            case "plus_minus":
                $this->resolvePlusMinus(
                    $pending_card_id,
                    $card_def,
                    $target_id,
                );
                break;

            case "help":
                $this->resolveHelp($pending_card_id, $card_def, $target_id);
                break;

            case "action":
                if (
                    $this->resolveAction(
                        $pending_card_id,
                        $card_def,
                        $target_id,
                        $player_id,
                    ) === false
                ) {
                    return; // Action handled its own state transition
                }
                break;

            case "sasquatch":
                $this->resolveSasquatch(
                    $pending_card_id,
                    $card_def,
                    $target_id,
                    $player_id,
                );
                return; // Sasquatch may transition to different states

            default:
                // Move unhandled cards to discard
                $this->cards->moveCard($pending_card_id, "discard");
                break;
        }

        $this->gamestate->nextState("next");
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Card effect resolution helpers
    //////////////////////////////////////////////////////////////////////////////

    /**
     * Resolve an equipment card: place it in the target player's equipment area.
     * Enforces uniqueness by subtype (one axe, one gloves, one boots).
     */
    function resolveEquipment($card_id, $card_def, $target_id)
    {
        $subtype = $card_def["subtype"];

        // Check for existing equipment of the same subtype — discard old one
        $existing = self::getObjectFromDB(
            "SELECT equipment_id, card_id FROM player_equipment
             WHERE player_id = $target_id AND equipment_type = '$subtype'",
        );

        if ($existing) {
            // Remove old equipment from table and move card to discard
            self::DbQuery(
                "DELETE FROM player_equipment WHERE equipment_id = " .
                    $existing["equipment_id"],
            );
            $this->cards->moveCard($existing["card_id"], "discard");
            $old_card_def = $this->getCardDefinitionForCardId(
                $existing["card_id"],
            );

            self::notifyAllPlayers(
                "equipmentDiscarded",
                clienttranslate('${player_name}\'s ${card_name} is discarded'),
                [
                    "player_id" => $target_id,
                    "player_name" => self::getPlayerNameById($target_id),
                    "card_name" => $old_card_def["name"],
                    "card_id" => $existing["card_id"],
                ],
            );
        }

        // Place new equipment
        self::DbQuery(
            "INSERT INTO player_equipment (player_id, card_id, equipment_type)
             VALUES ($target_id, $card_id, '$subtype')",
        );
        $this->cards->moveCard($card_id, "equipment", $target_id);

        self::notifyAllPlayers(
            "equipmentPlaced",
            clienttranslate('${player_name} equips ${card_name}'),
            [
                "player_id" => $target_id,
                "player_name" => self::getPlayerNameById($target_id),
                "card_name" => $card_def["name"],
                "card_id" => $card_id,
                "card_type_arg" => $card_def["id"],
                "equipment_type" => $subtype,
            ],
        );
    }

    /**
     * Resolve a plus/minus modifier card: apply it to the target player.
     * Checks preventable_by for immediate prevention.
     */
    function resolvePlusMinus($card_id, $card_def, $target_id)
    {
        // Check if the modifier is preventable
        if (isset($card_def["preventable_by"])) {
            $prevents_card_def_id = $card_def["preventable_by"];
            // Check if target has the preventing equipment
            $has_prevention = self::getObjectFromDB(
                "SELECT pe.equipment_id FROM player_equipment pe
                 JOIN card c ON pe.card_id = c.card_id
                 WHERE pe.player_id = $target_id
                 AND c.card_type_arg = $prevents_card_def_id
                 AND c.card_type = 'jack'",
            );

            if ($has_prevention) {
                // Card is prevented — discard with no effect
                $this->cards->moveCard($card_id, "discard");
                $preventing_def = $this->jack_cards[$prevents_card_def_id];

                self::notifyAllPlayers(
                    "modifierPrevented",
                    clienttranslate(
                        '${card_name} is prevented by ${player_name}\'s ${preventing_card}',
                    ),
                    [
                        "player_id" => $target_id,
                        "player_name" => self::getPlayerNameById($target_id),
                        "card_name" => $card_def["name"],
                        "card_id" => $card_id,
                        "preventing_card" => $preventing_def["name"],
                    ],
                );
                return;
            }
        }

        // Apply the modifier
        $modifier_value = $card_def["modifier"];
        $is_persistent =
            isset($card_def["persistent"]) && $card_def["persistent"] ? 1 : 0;
        $modifier_type = strtolower(str_replace(" ", "_", $card_def["name"]));

        self::DbQuery(
            "INSERT INTO player_modifiers (player_id, card_id, modifier_type, modifier_value, is_persistent)
             VALUES ($target_id, $card_id, '$modifier_type', $modifier_value, $is_persistent)",
        );
        $this->cards->moveCard($card_id, "modifier", $target_id);

        self::notifyAllPlayers(
            "modifierApplied",
            clienttranslate('${card_name} is applied to ${player_name}'),
            [
                "player_id" => $target_id,
                "player_name" => self::getPlayerNameById($target_id),
                "card_name" => $card_def["name"],
                "card_id" => $card_id,
                "card_type_arg" => $card_def["id"],
                "modifier_value" => $modifier_value,
                "is_persistent" => $is_persistent,
            ],
        );
    }

    /**
     * Resolve a help card: place it in the target player's help area.
     * Enforces uniqueness by card definition id.
     */
    function resolveHelp($card_id, $card_def, $target_id)
    {
        $help_type = strtolower(str_replace(" ", "_", $card_def["name"]));
        $card_def_id = $card_def["id"];

        // Check for existing help card of same type — discard old one
        $existing = self::getObjectFromDB(
            "SELECT ph.help_id, ph.card_id FROM player_help ph
             JOIN card c ON ph.card_id = c.card_id
             WHERE ph.player_id = $target_id
             AND c.card_type_arg = $card_def_id
             AND c.card_type = 'jack'",
        );

        $replaced_card_id = null;
        if ($existing) {
            $replaced_card_id = $existing["card_id"];
            self::DbQuery(
                "DELETE FROM player_help WHERE help_id = " .
                    $existing["help_id"],
            );
            $this->cards->moveCard($existing["card_id"], "discard");
        }

        // Place new help card
        self::DbQuery(
            "INSERT INTO player_help (player_id, card_id, help_type)
             VALUES ($target_id, $card_id, '$help_type')",
        );
        $this->cards->moveCard($card_id, "help", $target_id);

        self::notifyAllPlayers(
            "helpPlaced",
            clienttranslate('${player_name} gains ${card_name}'),
            [
                "player_id" => $target_id,
                "player_name" => self::getPlayerNameById($target_id),
                "card_name" => $card_def["name"],
                "card_id" => $card_id,
                "card_type_arg" => $card_def["id"],
                "help_type" => $help_type,
                "replaced_card_id" => $replaced_card_id,
            ],
        );
    }

    /**
     * Resolve an action card based on its specific card definition id.
     */
    function resolveAction($card_id, $card_def, $target_id, $player_id)
    {
        $card_def_id = $card_def["id"];

        switch ($card_def_id) {
            case 5: // Paul Bunyan — all trees in play are chopped down
                $this->resolvePaulBunyan($card_id);
                break;

            case 8: // Axe Break — target's axe is discarded
                $this->resolveAxeBreak($card_id, $target_id);
                break;

            case 24: // Forest Fire — all trees in play are destroyed
                $this->resolveForestFire($card_id);
                break;

            case 6: // Steal Equipment
                $this->resolveStealEquipment($card_id, $target_id, $player_id);
                break;

            case 19: // Steal Axe
                $this->resolveStealAxe($card_id, $target_id, $player_id);
                break;

            case 15: // Lure Help
                $this->resolveLureHelp($card_id, $target_id, $player_id);
                break;

            case 20: // Switch Tags
                if (
                    $this->resolveSwitchTags(
                        $card_id,
                        $target_id,
                        $player_id,
                    ) === false
                ) {
                    return false; // resolveSwitchTags handled its own state transition
                }
                break;

            case 21: // Tree Hugger — target loses next turn
                $this->resolveTreeHugger($card_id, $target_id);
                break;

            default:
                // Unknown action card, discard it
                $this->cards->moveCard($card_id, "discard");
                break;
        }
    }

    /**
     * Paul Bunyan: All trees in play are chopped down.
     * Each player's active tree moves to their cut pile with full points.
     */
    function resolvePaulBunyan($card_id)
    {
        $this->cards->moveCard($card_id, "discard");

        $active_trees = self::getObjectListFromDB(
            "SELECT tree_id, player_id, card_id, tree_type, chops_required, points_value
             FROM active_tree",
        );

        foreach ($active_trees as $tree) {
            // Move tree to cut pile
            $cut_order = self::getUniqueValueFromDB(
                "SELECT COALESCE(MAX(cut_order), 0) + 1 FROM cut_tree
                 WHERE player_id = " . $tree["player_id"],
            );

            self::DbQuery(
                "INSERT INTO cut_tree (player_id, card_id, tree_type, points_value, cut_order)
                 VALUES (" .
                    $tree["player_id"] .
                    ", " .
                    $tree["card_id"] .
                    ", '" .
                    $tree["tree_type"] .
                    "', " .
                    $tree["points_value"] .
                    ", $cut_order)",
            );
            self::DbQuery(
                "DELETE FROM active_tree WHERE tree_id = " . $tree["tree_id"],
            );
            $this->cards->moveCard(
                $tree["card_id"],
                "treecut",
                $tree["player_id"],
            );

            $tree_def = $this->getCardDefinitionForCardId($tree["card_id"]);

            $cut_tree_id = self::DbGetLastId();

            $this->bga->playerScore->inc(
                $tree["player_id"],
                $tree["points_value"],
            );
            $new_score = self::getUniqueValueFromDB(
                "SELECT player_score FROM player WHERE player_id = " .
                    $tree["player_id"],
            );

            self::notifyAllPlayers(
                "treeCompleted",
                clienttranslate(
                    'Paul Bunyan chops down ${player_name}\'s ${tree_name}!',
                ),
                [
                    "player_id" => $tree["player_id"],
                    "player_name" => self::getPlayerNameById(
                        $tree["player_id"],
                    ),
                    "tree_name" => $tree_def["name"],
                    "points" => $tree["points_value"],
                    "card_type_arg" => $tree_def["id"],
                    "tree_id" => $cut_tree_id,
                    "new_score" => $new_score,
                ],
            );
        }
    }

    /**
     * Axe Break: Target player's axe is discarded.
     * Cannot break a Titanium Axe.
     */
    function resolveAxeBreak($card_id, $target_id)
    {
        $this->cards->moveCard($card_id, "discard");

        // Find target's axe
        $axe = self::getObjectFromDB(
            "SELECT pe.equipment_id, pe.card_id FROM player_equipment pe
             WHERE pe.player_id = $target_id AND pe.equipment_type = 'axe'",
        );

        if ($axe) {
            $axe_card_def = $this->getCardDefinitionForCardId($axe["card_id"]);

            // Titanium Axe cannot be broken
            if (
                isset($axe_card_def["unbreakable"]) &&
                $axe_card_def["unbreakable"]
            ) {
                self::notifyAllPlayers(
                    "axeBreakFailed",
                    clienttranslate(
                        '${player_name}\'s ${card_name} cannot be broken!',
                    ),
                    [
                        "player_id" => $target_id,
                        "player_name" => self::getPlayerNameById($target_id),
                        "card_name" => $axe_card_def["name"],
                    ],
                );
                return;
            }

            // Discard the axe
            self::DbQuery(
                "DELETE FROM player_equipment WHERE equipment_id = " .
                    $axe["equipment_id"],
            );
            $this->cards->moveCard($axe["card_id"], "discard");

            self::notifyAllPlayers(
                "equipmentDiscarded",
                clienttranslate('${player_name}\'s ${card_name} breaks!'),
                [
                    "player_id" => $target_id,
                    "player_name" => self::getPlayerNameById($target_id),
                    "card_name" => $axe_card_def["name"],
                    "card_id" => $axe["card_id"],
                ],
            );
        }
    }

    /**
     * Forest Fire: All active trees are destroyed (no points scored).
     */
    function resolveForestFire($card_id)
    {
        $this->cards->moveCard($card_id, "discard");

        $active_trees = self::getObjectListFromDB(
            "SELECT tree_id, player_id, card_id FROM active_tree",
        );

        foreach ($active_trees as $tree) {
            self::DbQuery(
                "DELETE FROM active_tree WHERE tree_id = " . $tree["tree_id"],
            );
            $this->cards->moveCard($tree["card_id"], "discard");
        }

        self::notifyAllPlayers(
            "forestFire",
            clienttranslate("Forest Fire! All trees in play are destroyed!"),
            [
                "affected_players" => array_column($active_trees, "player_id"),
            ],
        );
    }

    /**
     * Steal Equipment: Take one equipment card from target.
     * If target has multiple, take the first one found.
     * TODO: Add selection sub-state when target has multiple equipment.
     */
    function resolveStealEquipment($card_id, $target_id, $player_id)
    {
        $this->cards->moveCard($card_id, "discard");

        $equipment = self::getObjectFromDB(
            "SELECT equipment_id, card_id, equipment_type FROM player_equipment
             WHERE player_id = $target_id LIMIT 1",
        );

        if ($equipment) {
            $this->transferEquipment($equipment, $target_id, $player_id);
        }
    }

    /**
     * Steal Axe: Take one axe card from target.
     */
    function resolveStealAxe($card_id, $target_id, $player_id)
    {
        $this->cards->moveCard($card_id, "discard");

        $axe = self::getObjectFromDB(
            "SELECT equipment_id, card_id, equipment_type FROM player_equipment
             WHERE player_id = $target_id AND equipment_type = 'axe' LIMIT 1",
        );

        if ($axe) {
            $this->transferEquipment($axe, $target_id, $player_id);
        }
    }

    /**
     * Transfer an equipment card from one player to another.
     * Handles uniqueness — if receiver already has same subtype, old one is discarded.
     */
    function transferEquipment($equipment, $from_id, $to_id)
    {
        $subtype = $equipment["equipment_type"];
        $eq_card_id = $equipment["card_id"];
        $eq_card_def = $this->getCardDefinitionForCardId($eq_card_id);

        // Remove from source player
        self::DbQuery(
            "DELETE FROM player_equipment WHERE equipment_id = " .
                $equipment["equipment_id"],
        );

        // Check if target already has same subtype — discard old
        $replaced_card_id = null;
        $existing = self::getObjectFromDB(
            "SELECT equipment_id, card_id FROM player_equipment
             WHERE player_id = $to_id AND equipment_type = '$subtype'",
        );
        if ($existing) {
            $replaced_card_id = $existing["card_id"];
            self::DbQuery(
                "DELETE FROM player_equipment WHERE equipment_id = " .
                    $existing["equipment_id"],
            );
            $this->cards->moveCard($existing["card_id"], "discard");
        }

        // Place in target
        self::DbQuery(
            "INSERT INTO player_equipment (player_id, card_id, equipment_type)
             VALUES ($to_id, $eq_card_id, '$subtype')",
        );
        $this->cards->moveCard($eq_card_id, "equipment", $to_id);

        self::notifyAllPlayers(
            "equipmentStolen",
            clienttranslate(
                '${player_name} takes ${card_name} from ${target_name}',
            ),
            [
                "player_id" => $to_id,
                "player_name" => self::getPlayerNameById($to_id),
                "target_id" => $from_id,
                "target_name" => self::getPlayerNameById($from_id),
                "card_name" => $eq_card_def["name"],
                "card_id" => $eq_card_id,
                "card_type_arg" => $eq_card_def["id"],
                "equipment_type" => $subtype,
                "replaced_card_id" => $replaced_card_id,
            ],
        );
    }

    /**
     * Lure Help: Steal Apprentice or Long Saw and Partner from target.
     * Takes the first help card found.
     * TODO: Add selection sub-state when target has multiple help cards.
     */
    function resolveLureHelp($card_id, $target_id, $player_id)
    {
        $this->cards->moveCard($card_id, "discard");

        $help = self::getObjectFromDB(
            "SELECT help_id, card_id, help_type FROM player_help
             WHERE player_id = $target_id LIMIT 1",
        );

        if ($help) {
            $help_card_def = $this->getCardDefinitionForCardId(
                $help["card_id"],
            );

            // Remove from target
            self::DbQuery(
                "DELETE FROM player_help WHERE help_id = " . $help["help_id"],
            );

            // Check if player already has same help type — discard old
            $existing = self::getObjectFromDB(
                "SELECT ph.help_id, ph.card_id FROM player_help ph
                 JOIN card c ON ph.card_id = c.card_id
                 WHERE ph.player_id = $player_id
                 AND c.card_type_arg = " .
                    $help_card_def["id"] .
                    "
                 AND c.card_type = 'jack'",
            );
            $replaced_card_id = null;
            if ($existing) {
                $replaced_card_id = $existing["card_id"];
                self::DbQuery(
                    "DELETE FROM player_help WHERE help_id = " .
                        $existing["help_id"],
                );
                $this->cards->moveCard($existing["card_id"], "discard");
            }

            // Place in player's help area
            $help_type = $help["help_type"];
            self::DbQuery(
                "INSERT INTO player_help (player_id, card_id, help_type)
                 VALUES ($player_id, " .
                    $help["card_id"] .
                    ", '$help_type')",
            );
            $this->cards->moveCard($help["card_id"], "help", $player_id);

            self::notifyAllPlayers(
                "helpStolen",
                clienttranslate(
                    '${player_name} lures ${card_name} from ${target_name}',
                ),
                [
                    "player_id" => $player_id,
                    "player_name" => self::getPlayerNameById($player_id),
                    "target_id" => $target_id,
                    "target_name" => self::getPlayerNameById($target_id),
                    "card_name" => $help_card_def["name"],
                    "card_id" => $help["card_id"],
                    "card_type_arg" => $help_card_def["id"],
                    "replaced_card_id" => $replaced_card_id,
                ],
            );
        }
    }

    /**
     * Switch Tags: Exchange a cut tree with target player.
     * Uses tree selections stored in globals by actSelectTreesToSwitch.
     * Returns false if selections are missing/invalid (transitions back to selection state).
     */
    function resolveSwitchTags($card_id, $target_id, $player_id)
    {
        $my_tree_id = $this->globals->get("switch_tags_my_tree", null);
        $target_tree_id = $this->globals->get("switch_tags_target_tree", null);

        if (!$my_tree_id || !$target_tree_id) {
            $this->gamestate->nextState("selectTreesToSwitch");
            return false;
        }

        $my_tree = self::getObjectFromDB(
            "SELECT ct.cut_tree_id, ct.card_id, ct.tree_type, ct.points_value,
                    c.card_type_arg
             FROM cut_tree ct
             JOIN card c ON ct.card_id = c.card_id
             WHERE ct.cut_tree_id = " . intval($my_tree_id),
        );
        $their_tree = self::getObjectFromDB(
            "SELECT ct.cut_tree_id, ct.card_id, ct.tree_type, ct.points_value,
                    c.card_type_arg
             FROM cut_tree ct
             JOIN card c ON ct.card_id = c.card_id
             WHERE ct.cut_tree_id = " . intval($target_tree_id),
        );

        if (!$my_tree || !$their_tree) {
            $this->globals->set("switch_tags_my_tree", null);
            $this->globals->set("switch_tags_target_tree", null);
            $this->gamestate->nextState("selectTreesToSwitch");
            return false;
        }

        $this->cards->moveCard($card_id, "discard");

        // Swap ownership
        self::DbQuery(
            "UPDATE cut_tree SET player_id = $target_id
             WHERE cut_tree_id = " . $my_tree["cut_tree_id"],
        );
        self::DbQuery(
            "UPDATE cut_tree SET player_id = $player_id
             WHERE cut_tree_id = " . $their_tree["cut_tree_id"],
        );
        $this->cards->moveCard($my_tree["card_id"], "treecut", $target_id);
        $this->cards->moveCard($their_tree["card_id"], "treecut", $player_id);

        // Update scores
        $my_new_score = self::getUniqueValueFromDB(
            "SELECT COALESCE(SUM(points_value), 0) FROM cut_tree
             WHERE player_id = $player_id",
        );
        $their_new_score = self::getUniqueValueFromDB(
            "SELECT COALESCE(SUM(points_value), 0) FROM cut_tree
             WHERE player_id = $target_id",
        );
        $this->bga->playerScore->set($player_id, $my_new_score);
        $this->bga->playerScore->set($target_id, $their_new_score);

        self::notifyAllPlayers(
            "switchTags",
            clienttranslate(
                '${player_name} switches a cut tree with ${target_name}',
            ),
            [
                "player_id" => $player_id,
                "player_name" => self::getPlayerNameById($player_id),
                "target_id" => $target_id,
                "target_name" => self::getPlayerNameById($target_id),
                "player_new_score" => $my_new_score,
                "target_new_score" => $their_new_score,
                "player_tree" => $my_tree,
                "target_tree" => $their_tree,
            ],
        );

        // Clean up globals
        $this->globals->set("switch_tags_my_tree", null);
        $this->globals->set("switch_tags_target_tree", null);
    }

    /**
     * Tree Hugger: Target loses their next turn.
     */
    function resolveTreeHugger($card_id, $target_id)
    {
        $this->cards->moveCard($card_id, "discard");

        self::DbQuery(
            "UPDATE player SET player_skip_next_turn = 1
             WHERE player_id = $target_id",
        );

        self::notifyAllPlayers(
            "treeHugger",
            clienttranslate('${player_name} loses their next turn!'),
            [
                "player_id" => $target_id,
                "player_name" => self::getPlayerNameById($target_id),
            ],
        );
    }

    /**
     * Resolve a sasquatch card. All sasquatch cards discard all help cards
     * in play (unless debunked — handled by reaction system before reaching here).
     */
    function resolveSasquatch($card_id, $card_def, $target_id, $player_id)
    {
        $card_def_id = $card_def["id"];

        // Global sasquatch effect: discard all help cards in play
        $this->discardAllHelpCards();

        switch ($card_def_id) {
            case 7: // That Darn Sasquatch — all equipment discarded
                $this->resolveThatDarnSasquatch($card_id);
                $this->gamestate->nextState("next");
                break;

            case 16: // Sasquatch Mating Season — target loses turn, may swap tree
                $this->resolveSasquatchMating($card_id, $target_id, $player_id);
                return; // Transitions to chooseTakeTree state

            case 27: // Sasquatch Sighting — opponents roll to save
                $this->cards->moveCard($card_id, "discard");
                $this->gamestate->nextState("sasquatchSighting");
                break;

            default:
                $this->cards->moveCard($card_id, "discard");
                $this->gamestate->nextState("next");
                break;
        }
    }

    /**
     * Discard all help cards in play for all players.
     * Called as a shared effect when any sasquatch card resolves.
     */
    function discardAllHelpCards()
    {
        $all_help = self::getObjectListFromDB(
            "SELECT help_id, player_id, card_id FROM player_help",
        );

        foreach ($all_help as $help) {
            self::DbQuery(
                "DELETE FROM player_help WHERE help_id = " . $help["help_id"],
            );
            $this->cards->moveCard($help["card_id"], "discard");

            $help_card_def = $this->getCardDefinitionForCardId(
                $help["card_id"],
            );
            self::notifyAllPlayers(
                "helpDiscarded",
                clienttranslate(
                    '${player_name}\'s ${card_name} is scared away by the Sasquatch!',
                ),
                [
                    "player_id" => $help["player_id"],
                    "player_name" => self::getPlayerNameById(
                        $help["player_id"],
                    ),
                    "card_name" => $help_card_def["name"],
                    "card_id" => $help["card_id"],
                ],
            );
        }
    }

    /**
     * That Darn Sasquatch: All equipment in play is discarded.
     */
    function resolveThatDarnSasquatch($card_id)
    {
        $this->cards->moveCard($card_id, "discard");

        $all_equipment = self::getObjectListFromDB(
            "SELECT equipment_id, player_id, card_id FROM player_equipment",
        );

        foreach ($all_equipment as $equip) {
            self::DbQuery(
                "DELETE FROM player_equipment WHERE equipment_id = " .
                    $equip["equipment_id"],
            );
            $this->cards->moveCard($equip["card_id"], "discard");

            $equip_card_def = $this->getCardDefinitionForCardId(
                $equip["card_id"],
            );
            self::notifyAllPlayers(
                "equipmentDiscarded",
                clienttranslate('${player_name}\'s ${card_name} is discarded!'),
                [
                    "player_id" => $equip["player_id"],
                    "player_name" => self::getPlayerNameById(
                        $equip["player_id"],
                    ),
                    "card_name" => $equip_card_def["name"],
                    "card_id" => $equip["card_id"],
                ],
            );
        }
    }

    /**
     * Sasquatch Mating Season: Target loses next turn.
     * Active player is prompted to choose whether to take target's tree.
     */
    function resolveSasquatchMating($card_id, $target_id, $player_id)
    {
        $this->cards->moveCard($card_id, "discard");

        // Target loses next turn
        self::DbQuery(
            "UPDATE player SET player_skip_next_turn = 1
             WHERE player_id = $target_id",
        );

        self::notifyAllPlayers(
            "sasquatchMating",
            clienttranslate('${player_name} loses their next turn!'),
            [
                "player_id" => $target_id,
                "player_name" => self::getPlayerNameById($target_id),
                "active_id" => $player_id,
                "active_name" => self::getPlayerNameById($player_id),
            ],
        );

        // Check if target even has a tree to take
        $their_tree = self::getObjectFromDB(
            "SELECT tree_id FROM active_tree WHERE player_id = $target_id",
        );

        if ($their_tree) {
            // Prompt active player to choose
            $this->gamestate->nextState("chooseTakeTree");
        } else {
            // No tree to take, skip straight ahead
            $this->gamestate->nextState("next");
        }
    }

    /**
     * Args for chooseTakeTree state: provide target info so the UI can display it.
     */
    function argChooseTakeTree()
    {
        $target_id = self::getGameStateValue("reaction_pending_player");
        return [
            "target_id" => $target_id,
            "target_name" => self::getPlayerNameById($target_id),
        ];
    }

    /**
     * Action: player chooses whether to take the target's tree.
     */
    function actChooseTakeTree(bool $take_tree)
    {
        self::checkAction("actChooseTakeTree");

        $player_id = self::getActivePlayerId();
        $target_id = self::getGameStateValue("reaction_pending_player");

        if ($take_tree) {
            $my_tree = self::getObjectFromDB(
                "SELECT tree_id, card_id FROM active_tree
                 WHERE player_id = $player_id",
            );
            $their_tree = self::getObjectFromDB(
                "SELECT at.tree_id, at.card_id, at.tree_type, at.chop_count, at.chops_required, at.points_value,
                        c.card_type_arg
                 FROM active_tree at
                 JOIN card c ON at.card_id = c.card_id
                 WHERE at.player_id = $target_id",
            );

            if ($their_tree) {
                // Transfer target's tree to active player
                self::DbQuery(
                    "UPDATE active_tree SET player_id = $player_id
                     WHERE tree_id = " . $their_tree["tree_id"],
                );
                $this->cards->moveCard(
                    $their_tree["card_id"],
                    "treeactive",
                    $player_id,
                );

                // Discard active player's old tree if they had one
                if ($my_tree) {
                    self::DbQuery(
                        "DELETE FROM active_tree WHERE tree_id = " .
                            $my_tree["tree_id"],
                    );
                    $this->cards->moveCard($my_tree["card_id"], "discard");
                }

                self::notifyAllPlayers(
                    "treeTaken",
                    clienttranslate(
                        '${active_name} takes ${player_name}\'s tree!',
                    ),
                    [
                        "player_id" => $target_id,
                        "player_name" => self::getPlayerNameById($target_id),
                        "active_id" => $player_id,
                        "active_name" => self::getPlayerNameById($player_id),
                        "tree" => $their_tree,
                    ],
                );
            }
        } else {
            self::notifyAllPlayers(
                "treeTakeDeclined",
                clienttranslate(
                    '${active_name} declines to take ${player_name}\'s tree',
                ),
                [
                    "player_id" => $target_id,
                    "player_name" => self::getPlayerNameById($target_id),
                    "active_id" => $player_id,
                    "active_name" => self::getPlayerNameById($player_id),
                ],
            );
        }

        $this->gamestate->nextState("next");
    }

    /**
     * State: sasquatchSighting
     * All opponents roll to avoid losing their turn
     */
    function stSasquatchSighting()
    {
        $active_player_id = self::getActivePlayerId();
        $players = self::loadPlayersBasicInfos();

        // Set all opponents as multiactive
        $opponents = [];
        foreach ($players as $player_id => $player) {
            if ($player_id != $active_player_id) {
                $opponents[] = $player_id;
            }
        }

        $this->gamestate->setPlayersMultiactive($opponents, "next", false);
    }

    /**
     * State: contestRoll
     * Both players roll dice for contest
     */
    function stContestRoll()
    {
        // TODO: Implement contest logic in Phase 3
        // Not needed for base game (no contest cards)
        $this->gamestate->nextState("next");
    }

    /**
     * State: preChoppingRoll
     * Check if the player has a chopping tool. If yes, go to interactive roll state.
     * If no, skip directly to checkTreeComplete.
     */
    function stPreChoppingRoll()
    {
        $player_id = self::getActivePlayerId();

        $has_tree = self::getObjectFromDB(
            "SELECT tree_id FROM active_tree WHERE player_id = $player_id",
        );

        $has_apprentice =
            self::getObjectFromDB(
                "SELECT help_id FROM player_help
             WHERE player_id = $player_id AND help_type = 'apprentice'",
            ) !== null;

        if (
            !$has_tree ||
            (!$this->playerHasChoppingTool($player_id) && !$has_apprentice)
        ) {
            $player_name = self::getPlayerNameById($player_id);
            $reason = !$has_tree
                ? clienttranslate(
                    '${player_name} has no active tree — chopping roll skipped',
                )
                : clienttranslate(
                    '${player_name} has no axe or help — chopping roll skipped',
                );
            self::notifyAllPlayers("choppingSkipped", $reason, [
                "player_name" => $player_name,
                "player_id" => $player_id,
            ]);
            $this->gamestate->nextState("skip");
        } else {
            $this->gamestate->nextState("roll");
        }
    }

    /**
     * Check if a player has an axe or Long Saw & Partner.
     */
    function playerHasChoppingTool($player_id)
    {
        $long_saw = self::getObjectFromDB(
            "SELECT help_id FROM player_help
             WHERE player_id = $player_id AND help_type = 'long_saw_and_partner'",
        );
        if ($long_saw) {
            return true;
        }
        $axe = self::getObjectFromDB(
            "SELECT equipment_id FROM player_equipment
             WHERE player_id = $player_id AND equipment_type = 'axe'",
        );
        return $axe !== null;
    }

    /**
     * Args for choppingRoll state: tell the client how many dice will be rolled.
     */
    function argChoppingRoll()
    {
        $player_id = self::getActivePlayerId();
        $info = $this->getChoppingRollInfo($player_id);
        return [
            "total_dice" => $info["total_dice"],
            "using_long_saw" => $info["using_long_saw"],
            "has_apprentice" => $info["apprentice"] !== null,
            "tool_name" => $info["tool_name"],
        ];
    }

    /**
     * Args for choppingRollResult state: return stored results from globals.
     */
    function argChoppingRollResult()
    {
        return [
            "dice_results" => $this->globals->get("chop_dice_results"),
            "chops" => $this->globals->get("chop_chops"),
            "breaks" => $this->globals->get("chop_breaks"),
            "misses" => $this->globals->get("chop_misses"),
            "axe_broke" => $this->globals->get("chop_axe_broke"),
            "long_saw_failed" => $this->globals->get("chop_long_saw_failed"),
            "apprentice_die" => $this->globals->get("chop_apprentice_die"),
            "apprentice_chop" => $this->globals->get("chop_apprentice_chop"),
        ];
    }

    /**
     * Calculate chopping roll info for a player (dice count, tool type, etc.)
     */
    function getChoppingRollInfo($player_id)
    {
        $long_saw = self::getObjectFromDB(
            "SELECT ph.help_id, ph.card_id FROM player_help ph
             WHERE ph.player_id = $player_id AND ph.help_type = 'long_saw_and_partner'",
        );
        $axe = self::getObjectFromDB(
            "SELECT pe.equipment_id, pe.card_id, c.card_type_arg
             FROM player_equipment pe
             JOIN card c ON pe.card_id = c.card_id
             WHERE pe.player_id = $player_id AND pe.equipment_type = 'axe'",
        );

        $using_long_saw = $long_saw !== null;
        $tool_name = "";

        if ($using_long_saw) {
            $base_dice = $this->game_constants["long_saw_dice"];
            $tool_name = clienttranslate("Long Saw & Partner");
        } elseif ($axe) {
            $base_dice = $this->game_constants["base_axe_dice"];
            $axe_def = $this->jack_cards[intval($axe["card_type_arg"])];
            $tool_name = $axe_def["name"];
            if (isset($axe_def["modifier"])) {
                $base_dice += $axe_def["modifier"];
            }
        } else {
            // Apprentice only, no main dice
            $base_dice = 0;
            $tool_name = clienttranslate("Apprentice");
        }

        // No modifiers unless you have at least one base die
        $modifier_sum = 0;
        if ($base_dice > 0) {
            $modifier_sum = intval(
                self::getUniqueValueFromDB(
                    "SELECT COALESCE(SUM(modifier_value), 0) FROM player_modifiers WHERE player_id = $player_id",
                ),
            );
        }

        $total_dice = max(0, $base_dice + $modifier_sum);

        $apprentice = self::getObjectFromDB(
            "SELECT help_id FROM player_help
             WHERE player_id = $player_id AND help_type = 'apprentice'",
        );

        return [
            "total_dice" => $total_dice,
            "using_long_saw" => $using_long_saw,
            "apprentice" => $apprentice,
            "tool_name" => $tool_name,
            "long_saw" => $long_saw,
            "axe" => $axe,
        ];
    }

    /**
     * Action: Player clicks "Roll Dice" to perform their chopping roll.
     */
    function actChopRoll()
    {
        self::checkAction("actChopRoll");
        $player_id = self::getActivePlayerId();
        $player_name = self::getPlayerNameById($player_id);

        $info = $this->getChoppingRollInfo($player_id);
        $total_dice = $info["total_dice"];
        $using_long_saw = $info["using_long_saw"];
        $long_saw = $info["long_saw"];
        $axe = $info["axe"];
        $apprentice = $info["apprentice"];
        $did_chopping_roll = false;

        // Roll the dice
        $dice_results = [];
        $chops = 0;
        $breaks = 0;
        $misses = 0;
        for ($i = 0; $i < $total_dice; $i++) {
            $die = bga_rand(1, 6);
            $dice_results[] = $die;
            if ($die <= 2) {
                $breaks++;
            } elseif ($die == 3) {
                $misses++;
            } else {
                $chops++;
            }
        }

        // Check for axe break / Long Saw failure
        $axe_broke = false;
        $long_saw_failed = false;
        if ($using_long_saw) {
            $did_chopping_roll = true;
            if (
                $breaks + $misses >=
                $this->game_constants["long_saw_break_threshold"]
            ) {
                $long_saw_failed = true;
            }
        } elseif ($axe) {
            $did_chopping_roll = true;
            $axe_def = $this->jack_cards[intval($axe["card_type_arg"])];
            $is_unbreakable =
                isset($axe_def["unbreakable"]) && $axe_def["unbreakable"];
            if ($breaks >= $this->game_constants["axe_break_threshold"]) {
                if ($is_unbreakable) {
                    self::notifyAllPlayers(
                        "axeBreakFailed",
                        clienttranslate(
                            '${player_name}\'s ${card_name} cannot be broken!',
                        ),
                        [
                            "player_id" => $player_id,
                            "player_name" => $player_name,
                            "card_name" => $axe_def["name"],
                        ],
                    );
                } else {
                    $axe_broke = true;
                }
            }
        }

        // Apply chops to active tree
        $tree = self::getObjectFromDB(
            "SELECT tree_id, chop_count, chops_required FROM active_tree WHERE player_id = $player_id",
        );
        $new_chop_count = 0;
        if ($tree && $chops > 0) {
            $new_chop_count = min(
                intval($tree["chop_count"]) + $chops,
                intval($tree["chops_required"]),
            );
            self::DbQuery(
                "UPDATE active_tree SET chop_count = $new_chop_count WHERE tree_id = " .
                    $tree["tree_id"],
            );
        } elseif ($tree) {
            $new_chop_count = intval($tree["chop_count"]);
        }

        // Apprentice roll (separate from main roll)
        $apprentice_die = null;
        $apprentice_chop = 0;
        if ($apprentice) {
            $apprentice_die = bga_rand(1, 6);
            $apprentice_chop = $apprentice_die >= 4 ? 1 : 0;

            if ($apprentice_chop && $tree) {
                $new_chop_count = min(
                    $new_chop_count + 1,
                    intval($tree["chops_required"]),
                );
                self::DbQuery(
                    "UPDATE active_tree SET chop_count = $new_chop_count WHERE tree_id = " .
                        $tree["tree_id"],
                );
            }
        }

        // Store results in globals for the result state
        $this->globals->set("chop_dice_results", $dice_results);
        $this->globals->set("chop_chops", $chops);
        $this->globals->set("chop_breaks", $breaks);
        $this->globals->set("chop_misses", $misses);
        $this->globals->set("chop_axe_broke", $axe_broke);
        $this->globals->set("chop_long_saw_failed", $long_saw_failed);
        $this->globals->set("chop_apprentice_die", $apprentice_die);
        $this->globals->set("chop_apprentice_chop", $apprentice_chop);

        // Send main roll notification
        self::notifyAllPlayers(
            "choppingRoll",
            clienttranslate(
                '${player_name} rolls ${total_dice} dice: ${chops} chop(s), ${breaks} break(s), ${misses} miss(es)',
            ),
            [
                "player_id" => $player_id,
                "player_name" => $player_name,
                "dice_results" => $dice_results,
                "chops" => $chops,
                "breaks" => $breaks,
                "misses" => $misses,
                "total_dice" => $total_dice,
                "new_chop_count" => $new_chop_count,
                "using_long_saw" => $using_long_saw,
                "axe_broke" => $axe_broke,
                "long_saw_failed" => $long_saw_failed,
            ],
        );

        // Send apprentice roll notification if applicable
        if ($apprentice_die !== null) {
            self::notifyAllPlayers(
                "apprenticeRoll",
                clienttranslate(
                    '${player_name}\'s Apprentice rolls ${die_result}: ${result_text}',
                ),
                [
                    "player_id" => $player_id,
                    "player_name" => $player_name,
                    "die_result" => $apprentice_die,
                    "is_chop" => $apprentice_chop,
                    "result_text" => $apprentice_chop
                        ? clienttranslate("a chop!")
                        : clienttranslate("no chop"),
                    "new_chop_count" => $new_chop_count,
                ],
            );
        }

        // Handle axe break
        if ($axe_broke) {
            self::DbQuery(
                "DELETE FROM player_equipment WHERE equipment_id = " .
                    $axe["equipment_id"],
            );
            $this->cards->moveCard($axe["card_id"], "discard");
            self::notifyAllPlayers(
                "equipmentDiscarded",
                clienttranslate('${player_name}\'s axe breaks!'),
                [
                    "player_id" => $player_id,
                    "player_name" => $player_name,
                    "card_id" => $axe["card_id"],
                ],
            );
        }

        // Handle Long Saw failure
        if ($long_saw_failed) {
            $next_player = self::getPlayerAfter($player_id);
            self::DbQuery(
                "DELETE FROM player_help WHERE help_id = " .
                    $long_saw["help_id"],
            );
            $existing = self::getObjectFromDB(
                "SELECT help_id FROM player_help WHERE player_id = $next_player AND help_type = 'long_saw_and_partner'",
            );
            if (!$existing) {
                self::DbQuery(
                    "INSERT INTO player_help (player_id, card_id, help_type)
                     VALUES ($next_player, " .
                        $long_saw["card_id"] .
                        ", 'long_saw_and_partner')",
                );
                $this->cards->moveCard(
                    $long_saw["card_id"],
                    "help",
                    $next_player,
                );
                self::notifyAllPlayers(
                    "helpStolen",
                    clienttranslate(
                        '${original_owner_name}\'s Long Saw breaks! It passes to ${player_name}',
                    ),
                    [
                        "player_id" => $next_player,
                        "player_name" => self::getPlayerNameById($next_player),
                        "target_id" => $player_id,
                        "original_owner_name" => $player_name,
                        "card_id" => $long_saw["card_id"],
                        "card_type_arg" => 25,
                        "help_type" => "long_saw_and_partner",
                    ],
                );
            } else {
                $this->cards->moveCard($long_saw["card_id"], "discard");
                self::notifyAllPlayers(
                    "helpDiscarded",
                    clienttranslate(
                        '${player_name}\'s Long Saw breaks and is discarded!',
                    ),
                    [
                        "player_id" => $player_id,
                        "player_name" => $player_name,
                        "card_id" => $long_saw["card_id"],
                    ],
                );
            }
        }

        // Clean up non-persistent modifiers if performed chopping roll
        if ($did_chopping_roll) {
            $this->cleanupNonPersistentModifiers($player_id);
        }

        $this->gamestate->nextState("next");
    }

    /**
     * Action: Player clicks "Continue" after reviewing chopping roll results.
     */
    function actConfirmChopResult()
    {
        self::checkAction("actConfirmChopResult");
        $this->gamestate->nextState("next");
    }

    /**
     * Remove non-persistent modifiers for a player and discard their cards.
     */
    function cleanupNonPersistentModifiers($player_id)
    {
        $modifiers = self::getObjectListFromDB(
            "SELECT modifier_id, card_id FROM player_modifiers
             WHERE player_id = $player_id AND is_persistent = 0",
        );

        $player_name = self::getPlayerNameById($player_id);

        foreach ($modifiers as $mod) {
            $card_def = $this->getCardDefinitionForCardId($mod["card_id"]);
            self::DbQuery(
                "DELETE FROM player_modifiers WHERE modifier_id = " .
                    $mod["modifier_id"],
            );
            $this->cards->moveCard($mod["card_id"], "discard");
            self::notifyAllPlayers(
                "modifierRemoved",
                clienttranslate(
                    '${card_name} is discarded from ${player_name}',
                ),
                [
                    "player_id" => $player_id,
                    "player_name" => $player_name,
                    "card_name" => $card_def["name"],
                    "card_id" => $mod["card_id"],
                ],
            );
        }
    }

    /**
     * State: checkTreeComplete
     * Check if player's tree is complete
     */
    function stCheckTreeComplete()
    {
        $player_id = self::getActivePlayerId();

        // Get active tree
        $sql = "SELECT tree_id, chop_count, chops_required
                FROM active_tree WHERE player_id = $player_id";
        $tree = self::getObjectFromDB($sql);

        if ($tree && $tree["chop_count"] >= $tree["chops_required"]) {
            $this->gamestate->nextState("treeComplete");
        } else {
            $this->gamestate->nextState("continue");
        }
    }

    /**
     * State: treeCompleted
     * Move completed tree to cut pile and check for win
     */
    function stTreeCompleted()
    {
        $player_id = self::getActivePlayerId();

        // Get the completed tree
        $sql = "SELECT * FROM active_tree WHERE player_id = $player_id";
        $tree = self::getObjectFromDB($sql);

        if ($tree) {
            // Get next cut order number
            $sql = "SELECT MAX(cut_order) as max_order FROM cut_tree WHERE player_id = $player_id";
            $max_order = self::getUniqueValueFromDB($sql);
            $cut_order = ($max_order ? $max_order : 0) + 1;

            // Move to cut pile
            $sql = "INSERT INTO cut_tree (player_id, card_id, tree_type, points_value, cut_order)
                    VALUES ($player_id, {$tree["card_id"]}, '{$tree["tree_type"]}', {$tree["points_value"]}, $cut_order)";
            self::DbQuery($sql);
            $cut_tree_id = self::DbGetLastId();

            // Remove from active trees
            $sql = "DELETE FROM active_tree WHERE tree_id = {$tree["tree_id"]}";
            self::DbQuery($sql);

            // Update player score
            $this->bga->playerScore->inc($player_id, $tree["points_value"]);
            self::DbQuery(
                "UPDATE player SET player_current_tree_id = NULL WHERE player_id = $player_id",
            );

            // Update stats
            self::incStat(1, "trees_chopped", $player_id);
            self::incStat(1, "total_trees_chopped");

            // Notify
            $card_type_arg = self::getUniqueValueFromDB(
                "SELECT card_type_arg FROM card WHERE card_id = " .
                    $tree["card_id"],
            );
            $tree_def = $this->tree_cards[$card_type_arg];
            self::notifyAllPlayers(
                "treeCompleted",
                clienttranslate(
                    '${player_name} completes ${tree_name} for ${points} points!',
                ),
                [
                    "player_id" => $player_id,
                    "player_name" => self::getPlayerNameById($player_id),
                    "tree_name" => $tree_def["name"],
                    "points" => $tree["points_value"],
                    "tree_id" => $cut_tree_id,
                    "card_type_arg" => $card_type_arg,
                    "new_score" => self::getUniqueValueFromDB(
                        "SELECT player_score FROM player WHERE player_id = $player_id",
                    ),
                ],
            );

            // Check for win condition
            $score = self::getUniqueValueFromDB(
                "SELECT player_score FROM player WHERE player_id = $player_id",
            );
            if ($score >= $this->game_constants["winning_score"]) {
                $this->gamestate->nextState("gameEnd");
                return;
            }
        }

        $this->gamestate->nextState("continue");
    }

    /**
     * State: nextPlayer
     * Advance to next player, handling skip turns
     */
    function stNextPlayer()
    {
        $current_player_id = self::getActivePlayerId();

        // Check if current player should skip next turn
        $sql = "SELECT player_skip_next_turn FROM player WHERE player_id = $current_player_id";
        $skip = self::getUniqueValueFromDB($sql);

        if ($skip) {
            // Clear skip flag
            $sql = "UPDATE player SET player_skip_next_turn = 0 WHERE player_id = $current_player_id";
            self::DbQuery($sql);
        }

        // Move to next player
        $next_player_id = self::activeNextPlayer();

        // Check if next player should skip
        $sql = "SELECT player_skip_next_turn FROM player WHERE player_id = $next_player_id";
        $next_skip = self::getUniqueValueFromDB($sql);

        if ($next_skip) {
            // Skip this player
            $sql = "UPDATE player SET player_skip_next_turn = 0 WHERE player_id = $next_player_id";
            self::DbQuery($sql);

            self::notifyAllPlayers(
                "playerSkipped",
                clienttranslate('${player_name} skips their turn'),
                [
                    "player_id" => $next_player_id,
                    "player_name" => self::getPlayerNameById($next_player_id),
                ],
            );

            $this->gamestate->nextState("skipTurn");
        } else {
            $this->gamestate->nextState("nextTurn");
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:

        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).

        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state["name"];

        if ($state["type"] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state["type"] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, "");

            return;
        }

        throw new feException(
            "Zombie mode not supported at this game state: " . $statename,
        );
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:

        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.

    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        //        if( $from_version <= 1404301345 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        if( $from_version <= 1405061421 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        // Please add your future database scheme changes here
        //
        //
    }
}
