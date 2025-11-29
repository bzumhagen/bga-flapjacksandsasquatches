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
  * flapjacksandsasquatches.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */


require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );


class FlapjacksAndSasquatches extends Table
{
	function __construct( )
	{
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels( array(
            "current_round" => 10,
            "reaction_pending_card" => 11,
            "reaction_pending_player" => 12,
        ) );

        // Create deck manager
        $this->cards = $this->deckFactory->createDeck('card');
	}

    protected function getGameName( )
    {
		// Used for translations and stuff. Please do not modify.
        return "flapjacksandsasquatches";
    }

    /*
        setupNewGame:

        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame( $players, $options = array() )
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach( $players as $player_id => $player )
        {
            $color = array_shift( $default_colors );
            $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
        }
        $sql .= implode( $values, ',' );
        self::DbQuery( $sql );
        self::reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue( 'current_round', 0 );
        self::setGameStateInitialValue( 'reaction_pending_card', 0 );
        self::setGameStateInitialValue( 'reaction_pending_player', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        self::initStat( 'table', 'rounds_played', 0 );
        self::initStat( 'table', 'total_trees_chopped', 0 );

        self::initStat( 'player', 'trees_chopped', 0 );
        self::initStat( 'player', 'chops_rolled', 0 );
        self::initStat( 'player', 'axes_broken', 0 );
        self::initStat( 'player', 'sasquatch_cards_played', 0 );
        self::initStat( 'player', 'reaction_cards_played', 0 );

        // Setup the card decks
        $this->setupCardDecks();

        // Deal initial hands
        $players_info = self::loadPlayersBasicInfos();
        foreach( $players_info as $player_id => $player )
        {
            // Deal 3 cards to each player
            $cards = $this->cards->pickCards( $this->game_constants['starting_hand_size'], 'deck', $player_id );
        }

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
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
        $result = array();

        // Card definitions for client
        $result['redCards'] = $this->red_cards;
        $result['treeCards'] = $this->tree_cards;
        $result['constants'] = $this->game_constants;

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        $sql = "SELECT player_id id, player_score score, player_score_aux scoreAux,
                       player_skip_next_turn skipNextTurn, player_current_tree_id currentTreeId
                FROM player ";
        $result['players'] = self::getCollectionFromDb( $sql );

        // Get current player's hand
        $result['hand'] = $this->cards->getCardsInLocation( 'hand', $current_player_id );

        // Get deck counts
        $result['deckCount'] = $this->cards->countCardInLocation( 'deck' );
        $result['treeDeckCount'] = $this->cards->countCardInLocation( 'treedeck' );
        $result['discardCount'] = $this->cards->countCardInLocation( 'discard' );

        // Get all players' equipment (visible to all)
        $sql = "SELECT pe.player_id, pe.card_id, pe.equipment_type
                FROM player_equipment pe";
        $result['equipment'] = self::getObjectListFromDB( $sql );

        // Get all players' help cards (visible to all)
        $sql = "SELECT ph.player_id, ph.card_id, ph.help_type
                FROM player_help ph";
        $result['helpCards'] = self::getObjectListFromDB( $sql );

        // Get all players' active trees (visible to all)
        $sql = "SELECT at.tree_id, at.player_id, at.card_id, at.tree_type,
                       at.chop_count, at.chops_required, at.points_value
                FROM active_tree at";
        $result['activeTrees'] = self::getObjectListFromDB( $sql );

        // Get all players' cut trees (visible to all)
        $sql = "SELECT ct.cut_tree_id, ct.player_id, ct.card_id, ct.tree_type,
                       ct.points_value, ct.cut_order
                FROM cut_tree ct
                ORDER BY ct.cut_order ASC";
        $result['cutTrees'] = self::getObjectListFromDB( $sql );

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
        $max_score = self::getUniqueValueFromDB( $sql );

        $winning_score = $this->game_constants['winning_score'];

        // Return percentage (0-100)
        $progression = min( 100, ($max_score / $winning_score) * 100 );

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
        foreach( $this->red_cards as $card_key => $card_info )
        {
            if( isset($card_info['qty']) && $card_info['qty'] > 0 )
            {
                $deck_cards[] = [
                    'type' => $card_key,
                    'type_arg' => $card_info['id'],
                    'nbr' => $card_info['qty']
                ];
            }
        }

        // Create Tree Deck
        foreach( $this->tree_cards as $tree_key => $tree_info )
        {
            $deck_cards[] = [
                'type' => $tree_key,
                'type_arg' => $tree_info['id'],
                'nbr' => $tree_info['qty']
            ];
        }

        // Create all cards
        $this->cards->createCards( $deck_cards, 'deck' );

        // Separate tree cards into tree deck
        foreach( $this->tree_cards as $tree_key => $tree_info )
        {
            $tree_cards = $this->cards->getCardsOfType( $tree_key );
            foreach( $tree_cards as $card_id => $card )
            {
                $this->cards->moveCard( $card_id, 'treedeck' );
            }
        }

        // Shuffle both decks
        $this->cards->shuffle( 'deck' );       // Jack Deck
        $this->cards->shuffle( 'treedeck' );   // Tree Deck
    }

    /**
     * Get card information by ID
     */
    function getCardInfo( $card_id )
    {
        $card = $this->cards->getCard( $card_id );
        $card_type = $card['type'];

        // Check if it's a red card
        if( isset($this->red_cards[$card_type]) )
        {
            return array_merge( $card, $this->red_cards[$card_type] );
        }

        // Check if it's a tree card
        if( isset($this->tree_cards[$card_type]) )
        {
            return array_merge( $card, $this->tree_cards[$card_type] );
        }

        return $card;
    }

    /**
     * Get valid targets for a card effect
     */
    function getValidTargets( $card_info )
    {
        $players = self::loadPlayersBasicInfos();
        $active_player_id = self::getActivePlayerId();
        $valid_targets = array();

        // Most cards can target other players
        foreach( $players as $player_id => $player )
        {
            if( $player_id != $active_player_id )
            {
                $valid_targets[] = $player_id;
            }
        }

        return $valid_targets;
    }



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in flapjacksandsasquatches.action.php)
    */

    function playCard( $card_id )
    {
        self::checkAction( 'playCard' );
        $player_id = self::getActivePlayerId();

        // Get card info
        $card = $this->cards->getCard( $card_id );
        if( !$card || $card['location'] != 'hand' || $card['location_arg'] != $player_id ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }

        $card_info = $this->getCardInfo( $card );

        // Move card to limbo (pending state until resolution)
        $this->cards->moveCard( $card_id, 'limbo' );

        // Store pending card for reaction/resolution
        self::setGameStateValue( 'reaction_pending_card', $card_id );

        // Notify card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_info['name'],
            'card_id' => $card_id,
            'card_type' => $card_info['type']
        ) );

        // Check if card needs a target
        if( $this->cardNeedsTarget( $card_info ) ) {
            $this->gamestate->nextState( 'selectTarget' );
        } else {
            $this->gamestate->nextState( 'checkReaction' );
        }
    }

    function discardCard( $card_id )
    {
        self::checkAction( 'discardCard' );
        $player_id = self::getActivePlayerId();

        // Get card info
        $card = $this->cards->getCard( $card_id );
        if( !$card || $card['location'] != 'hand' || $card['location_arg'] != $player_id ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }

        $card_info = $this->getCardInfo( $card );

        // Move to discard
        $this->cards->moveCard( $card_id, 'discard' );

        // Notify discard
        self::notifyAllPlayers( "cardDiscarded", clienttranslate( '${player_name} discards a card' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_id' => $card_id
        ) );

        // Draw replacement card
        $this->gamestate->nextState( 'drawCard' );
    }

    function selectTarget( $target_id )
    {
        self::checkAction( 'selectTarget' );
        $player_id = self::getActivePlayerId();

        // Validate target exists and is not the active player
        $players = self::loadPlayersBasicInfos();
        if( !isset( $players[$target_id] ) ) {
            throw new BgaUserException( self::_("Invalid target player") );
        }
        if( $target_id == $player_id ) {
            throw new BgaUserException( self::_("You cannot target yourself") );
        }

        // Store target
        self::setGameStateValue( 'reaction_pending_player', $target_id );

        // Notify target selected
        self::notifyAllPlayers( "targetSelected", clienttranslate( '${player_name} targets ${target_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'target_id' => $target_id,
            'target_name' => self::getPlayerNameById( $target_id )
        ) );

        // Move to reaction check
        $this->gamestate->nextState( 'next' );
    }

    function playReaction( $card_id )
    {
        self::checkAction( 'playReaction' );
        $player_id = self::getCurrentPlayerId();

        // Get card info
        $card = $this->cards->getCard( $card_id );
        if( !$card || $card['location'] != 'hand' || $card['location_arg'] != $player_id ) {
            throw new BgaUserException( self::_("This card is not in your hand") );
        }

        $card_info = $this->getCardInfo( $card );

        // Validate it's a reaction card
        if( !isset( $card_info['can_react'] ) || !$card_info['can_react'] ) {
            throw new BgaUserException( self::_("This card cannot be played as a reaction") );
        }

        // Move to discard (reactions are discarded after use)
        $this->cards->moveCard( $card_id, 'discard' );

        // Deactivate this player
        $this->gamestate->setPlayerNonMultiactive( $player_id, 'next' );

        // Notify reaction played
        self::notifyAllPlayers( "reactionPlayed", clienttranslate( '${player_name} plays ${card_name} as a reaction!' ), array(
            'player_id' => $player_id,
            'player_name' => self::getPlayerNameById( $player_id ),
            'card_name' => $card_info['name'],
            'card_id' => $card_id,
            'card_type' => $card_info['type']
        ) );
    }

    function passReaction()
    {
        self::checkAction( 'passReaction' );
        $player_id = self::getCurrentPlayerId();

        // Deactivate this player
        $this->gamestate->setPlayerNonMultiactive( $player_id, 'next' );

        // Notify pass
        self::notifyAllPlayers( "reactionPassed", clienttranslate( '${player_name} passes' ), array(
            'player_id' => $player_id,
            'player_name' => self::getPlayerNameById( $player_id )
        ) );
    }

    // Helper: Determine if a card needs target selection
    function cardNeedsTarget( $card_info )
    {
        // Equipment and Help cards that affect other players need targets
        $targeting_cards = array(
            'equipment_double_bit_axe',
            'equipment_throwing_axe',
            'help_give_me_a_hand',
            'help_long_saw',
            'help_lumberjack_breakfast',
            'sasquatch_hatchet_happens',
            'sasquatch_splinter',
            'sasquatch_timber',
            'sasquatch_tree_hugger'
        );

        return in_array( $card_info['card_type'], $targeting_cards );
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
        $hand = $this->cards->getCardsInLocation( 'hand', $player_id );

        return array(
            'canPlay' => count($hand) > 0
        );
    }

    function argReactionWindow()
    {
        $pending_card_id = self::getGameStateValue( 'reaction_pending_card' );
        $target_player_id = self::getGameStateValue( 'reaction_pending_player' );
        $card_info = $this->getCardInfo( $pending_card_id );

        return array(
            'card_name' => $card_info['name'],
            'card_id' => $pending_card_id,
            'card_type' => $card_info['type'],
            'target_name' => $target_player_id ? self::getPlayerNameById( $target_player_id ) : '',
            'target_id' => $target_player_id
        );
    }

    function argSelectTarget()
    {
        $player_id = self::getActivePlayerId();
        $pending_card_id = self::getGameStateValue( 'reaction_pending_card' );
        $card_info = $this->getCardInfo( $pending_card_id );

        // Get valid targets based on card type
        $valid_targets = $this->getValidTargets( $card_info );

        return array(
            'card_name' => $card_info['name'],
            'valid_targets' => $valid_targets
        );
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
        $has_tree = self::getUniqueValueFromDB( $sql );

        if( !$has_tree )
        {
            $this->gamestate->nextState( 'drawTree' );
        }
        else
        {
            $this->gamestate->nextState( 'drawCard' );
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
        $tree_card = $this->cards->pickCard( 'treedeck', $player_id );

        if( $tree_card )
        {
            // Get tree info
            $tree_info = $this->tree_cards[$tree_card['type']];

            // Create active tree entry
            $sql = "INSERT INTO active_tree (player_id, card_id, tree_type, chop_count, chops_required, points_value)
                    VALUES ($player_id, {$tree_card['id']}, '{$tree_card['type']}', 0, {$tree_info['chops_required']}, {$tree_info['points']})";
            self::DbQuery( $sql );

            // Update player's current tree reference
            $tree_id = self::getUniqueValueFromDB( "SELECT LAST_INSERT_ID()" );
            $sql = "UPDATE player SET player_current_tree_id = $tree_id WHERE player_id = $player_id";
            self::DbQuery( $sql );

            // Notify all players
            $this->notify->all( 'treeDrawn', clienttranslate( '${player_name} draws ${tree_name}' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName(),
                'tree_name' => $tree_info['name'],
                'tree_id' => $tree_id,
                'card_id' => $tree_card['id'],
                'tree_type' => $tree_card['type'],
                'chops_required' => $tree_info['chops_required'],
                'points' => $tree_info['points']
            ));
        }

        $this->gamestate->nextState( 'next' );
    }

    /**
     * State: drawJackCard
     * Player draws a card from Jack Deck
     */
    function stDrawJackCard()
    {
        $player_id = self::getActivePlayerId();

        // Draw a card
        $card = $this->cards->pickCard( 'deck', $player_id );

        if( $card )
        {
            $card_info = $this->getCardInfo( $card['id'] );

            // Notify player privately
            $this->notify->player( $player_id, 'cardDrawn', '', array(
                'card' => $card_info
            ));

            // Notify all players publicly (no card details)
            $this->notify->all( 'cardDrawnPublic', clienttranslate( '${player_name} draws a card' ), array(
                'player_id' => $player_id,
                'player_name' => self::getActivePlayerName()
            ));
        }

        $this->gamestate->nextState( 'next' );
    }

    /**
     * State: checkReaction
     * Check if any players can react to the played card
     */
    function stCheckReaction()
    {
        $pending_card_id = self::getGameStateValue( 'reaction_pending_card' );
        $card_info = $this->getCardInfo( $pending_card_id );

        // Determine if this card can be reacted to
        $can_react = false;
        $active_players = array();

        // Check card type for reaction possibilities
        if( $card_info['type'] == 'sasquatch' )
        {
            // Debunk can block sasquatch cards
            $can_react = true;
            $reaction_card = 'reaction_debunk';
        }
        else if( in_array($card_info['id'], [29, 30]) ) // Switch Tags, Tree Hugger
        {
            // Paperwork can block these
            $can_react = true;
            $reaction_card = 'reaction_paperwork';
        }
        else if( in_array($card_info['id'], [10, 28]) ) // Steal Equipment, Steal Axe
        {
            // Northern Justice can block (but not in base game)
            $can_react = false; // Northern Justice qty = 0 in base game
        }

        if( $can_react )
        {
            // Find players who have the reaction card
            $players = self::loadPlayersBasicInfos();
            foreach( $players as $player_id => $player )
            {
                $hand = $this->cards->getCardsInLocation( 'hand', $player_id );
                foreach( $hand as $card )
                {
                    if( $card['type'] == $reaction_card )
                    {
                        $active_players[] = $player_id;
                        break;
                    }
                }
            }
        }

        if( count($active_players) > 0 )
        {
            // Set players as multiactive for reaction
            $this->gamestate->setPlayersMultiactive( $active_players, 'next', true );
            $this->gamestate->nextState( 'reaction' );
        }
        else
        {
            $this->gamestate->nextState( 'noReaction' );
        }
    }

    /**
     * State: resolveReaction
     * Process any reactions that were played
     */
    function stResolveReaction()
    {
        // Check if any reaction was played (stored in globals)
        $reaction_played = $this->globals->get( 'reaction_played', false );

        if( $reaction_played )
        {
            // Card was blocked, discard both cards
            $this->gamestate->nextState( 'blocked' );
        }
        else
        {
            // No reaction, proceed with card effect
            $this->gamestate->nextState( 'proceed' );
        }

        // Clear reaction tracking
        $this->globals->set( 'reaction_played', false );
    }

    /**
     * State: resolveCard
     * Execute the effect of the played card
     */
    function stResolveCard()
    {
        $pending_card_id = self::getGameStateValue( 'reaction_pending_card' );
        $card_info = $this->getCardInfo( $pending_card_id );

        // Route to appropriate handler based on card type
        // For now, just proceed to chopping roll
        // TODO: Implement specific card effects in Phase 3

        $this->gamestate->nextState( 'next' );
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
        $opponents = array();
        foreach( $players as $player_id => $player )
        {
            if( $player_id != $active_player_id )
            {
                $opponents[] = $player_id;
            }
        }

        $this->gamestate->setPlayersMultiactive( $opponents, 'next', false );
    }

    /**
     * State: contestRoll
     * Both players roll dice for contest
     */
    function stContestRoll()
    {
        // TODO: Implement contest logic in Phase 3
        // Not needed for base game (no contest cards)
        $this->gamestate->nextState( 'next' );
    }

    /**
     * State: choppingRoll
     * Player performs chopping roll if they have an axe
     */
    function stChoppingRoll()
    {
        $player_id = self::getActivePlayerId();

        // Check if player has equipment (axe or Long Saw & Partner)
        $sql = "SELECT equipment_type FROM player_equipment WHERE player_id = $player_id";
        $equipment = self::getObjectListFromDB( $sql );

        $has_chopping_tool = false;
        foreach( $equipment as $equip )
        {
            if( strpos($equip['equipment_type'], 'axe') !== false ||
                $equip['equipment_type'] == 'help_long_saw_and_partner' )
            {
                $has_chopping_tool = true;
                break;
            }
        }

        if( $has_chopping_tool )
        {
            // TODO: Implement dice rolling in Phase 4
            // For now, just skip
        }

        $this->gamestate->nextState( 'next' );
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
        $tree = self::getObjectFromDB( $sql );

        if( $tree && $tree['chop_count'] >= $tree['chops_required'] )
        {
            $this->gamestate->nextState( 'treeComplete' );
        }
        else
        {
            $this->gamestate->nextState( 'continue' );
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
        $tree = self::getObjectFromDB( $sql );

        if( $tree )
        {
            // Get next cut order number
            $sql = "SELECT MAX(cut_order) as max_order FROM cut_tree WHERE player_id = $player_id";
            $max_order = self::getUniqueValueFromDB( $sql );
            $cut_order = ($max_order ? $max_order : 0) + 1;

            // Move to cut pile
            $sql = "INSERT INTO cut_tree (player_id, card_id, tree_type, points_value, cut_order)
                    VALUES ($player_id, {$tree['card_id']}, '{$tree['tree_type']}', {$tree['points_value']}, $cut_order)";
            self::DbQuery( $sql );

            // Remove from active trees
            $sql = "DELETE FROM active_tree WHERE tree_id = {$tree['tree_id']}";
            self::DbQuery( $sql );

            // Update player score
            $sql = "UPDATE player SET player_score = player_score + {$tree['points_value']},
                                      player_current_tree_id = NULL
                    WHERE player_id = $player_id";
            self::DbQuery( $sql );

            // Update stats
            $this->playerStats->inc( 'trees_chopped', $player_id );
            $this->tableStats->inc( 'total_trees_chopped' );

            // Notify
            $tree_info = $this->tree_cards[$tree['tree_type']];
            $this->notify->all( 'treeCompleted', clienttranslate( '${player_name} completes ${tree_name} for ${points} points!' ), array(
                'player_id' => $player_id,
                'player_name' => self::getPlayerNameById( $player_id ),
                'tree_name' => $tree_info['name'],
                'points' => $tree['points_value'],
                'tree_id' => $tree['tree_id'],
                'new_score' => self::getUniqueValueFromDB( "SELECT player_score FROM player WHERE player_id = $player_id" )
            ));

            // Check for win condition
            $score = self::getUniqueValueFromDB( "SELECT player_score FROM player WHERE player_id = $player_id" );
            if( $score >= $this->game_constants['winning_score'] )
            {
                $this->gamestate->nextState( 'gameEnd' );
                return;
            }
        }

        $this->gamestate->nextState( 'continue' );
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
        $skip = self::getUniqueValueFromDB( $sql );

        if( $skip )
        {
            // Clear skip flag
            $sql = "UPDATE player SET player_skip_next_turn = 0 WHERE player_id = $current_player_id";
            self::DbQuery( $sql );
        }

        // Move to next player
        $next_player_id = self::activeNextPlayer();

        // Check if next player should skip
        $sql = "SELECT player_skip_next_turn FROM player WHERE player_id = $next_player_id";
        $next_skip = self::getUniqueValueFromDB( $sql );

        if( $next_skip )
        {
            // Skip this player
            $sql = "UPDATE player SET player_skip_next_turn = 0 WHERE player_id = $next_player_id";
            self::DbQuery( $sql );

            $this->notify->all( 'playerSkipped', clienttranslate( '${player_name} skips their turn' ), array(
                'player_id' => $next_player_id,
                'player_name' => self::getPlayerNameById( $next_player_id )
            ));

            $this->gamestate->nextState( 'skipTurn' );
        }
        else
        {
            $this->gamestate->nextState( 'nextTurn' );
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

    function zombieTurn( $state, $active_player )
    {
    	$statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState( "zombiePass" );
                	break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

            return;
        }

        throw new feException( "Zombie mode not supported at this game state: ".$statename );
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

    function upgradeTableDb( $from_version )
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
