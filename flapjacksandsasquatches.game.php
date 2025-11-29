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



//////////////////////////////////////////////////////////////////////////////
//////////// Player actions
////////////

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in flapjacksandsasquatches.action.php)
    */

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' );

        $player_id = self::getActivePlayerId();

        // Add your game logic to play a card there
        ...

        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );

    }

    */


//////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*

    Example for game state "MyGameState":

    function argMyGameState()
    {
        // Get some values from the current game situation in database...

        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }
    */

//////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    /*

    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...

        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }
    */

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
