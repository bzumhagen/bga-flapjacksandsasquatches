/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FlapjacksAndSasquatches implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * flapjacksandsasquatches.js
 *
 * FlapjacksAndSasquatches user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock"
],
function (dojo, declare) {
    return declare("bgagame.flapjacksandsasquatches", ebg.core.gamegui, {
        constructor: function(){
            console.log('flapjacksandsasquatches constructor');
              
            // Card dimensions
            this.cardWidth = 72;
            this.cardHeight = 96;
            
            // Card sprite URLs
            this.redCardSpriteUrl = g_gamethemeurl + 'img/red_cards.jpg';
            this.treeCardSpriteUrl = g_gamethemeurl + 'img/tree_cards.jpg';
            
            // Stock objects for different card types
            this.playerHand = null;
            this.treeDeck = null;
            this.jackDeck = null;
            this.discardPile = null;
        },
        
        /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */
        
        setup: function( gamedatas )
        {
            console.log( "Starting game setup" );
            
            // Setting up player boards
            for( var player_id in gamedatas.players )
            {
                var player = gamedatas.players[player_id];
                this.setupPlayerArea(player_id, player);
            }
            
            // Setup decks
            this.setupDecks(gamedatas);
            
            // Setup game notifications
            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        setupPlayerArea: function(player_id, player) {
            // Set player name and score
            dojo.place(this.format_block('jstpl_player_board', {
                name: player.name,
                score: 0
            }), 'player_' + player_id + '_area');
            
            // Setup player hand if it's the current player
            if (player_id == this.player_id) {
                this.playerHand = new ebg.stock();
                this.playerHand.create(this, $('myhand'), this.cardWidth, this.cardHeight);
                this.playerHand.image_items_per_row = 5;
                
                // Add card types to hand
                for (var key in gamedatas.redCards) {
                    let redCard = gamedatas.redCards[key];
                    this.playerHand.addItemType(redCard.id, redCard.id, this.redCardSpriteUrl, redCard.id-1);
                }
            }
        },

        setupDecks: function(gamedatas) {
            // Setup Tree Deck
            this.treeDeck = new ebg.stock();
            this.treeDeck.create(this, $('tree_deck'), this.cardWidth, this.cardHeight);
            
            // Setup Jack Deck
            this.jackDeck = new ebg.stock();
            this.jackDeck.create(this, $('jack_deck'), this.cardWidth, this.cardHeight);
            
            // Setup Discard Pile
            this.discardPile = new ebg.stock();
            this.discardPile.create(this, $('discard_pile'), this.cardWidth, this.cardHeight);
            
            // Add card types to decks
            for (var key in gamedatas.treeCards) {
                let treeCard = gamedatas.treeCards[key];
                this.treeDeck.addItemType(treeCard.id, treeCard.id, this.treeCardSpriteUrl, treeCard.id-1);
            }
            
            for (var key in gamedatas.redCards) {
                let redCard = gamedatas.redCards[key];
                this.jackDeck.addItemType(redCard.id, redCard.id, this.redCardSpriteUrl, redCard.id-1);
            }
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
                case 'gameSetup':
                    // Initial game setup state
                    break;
                    
                case 'playerTurn':
                    // Player's turn state
                    this.onEnteringPlayerTurn();
                    break;
            }
        },

        onEnteringPlayerTurn: function() {
            // Enable drawing from decks
            this.treeDeck.setSelectionMode(1);
            this.jackDeck.setSelectionMode(1);
            
            // Enable playing cards from hand
            if (this.playerHand) {
                this.playerHand.setSelectionMode(1);
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/flapjacksandsasquatches/flapjacksandsasquatches/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your flapjacksandsasquatches.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // Card played notification
            dojo.subscribe('cardPlayed', this, "notif_cardPlayed");
            
            // Card drawn notification
            dojo.subscribe('cardDrawn', this, "notif_cardDrawn");
            
            // Tree chopped notification
            dojo.subscribe('treeChopped', this, "notif_treeChopped");
        },  
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */

        onCardPlayed: function(evt) {
            console.log('onCardPlayed');
            
            // Preventing default browser reaction
            dojo.stopEvent(evt);
            
            // Check that this action is possible
            if (!this.checkAction('playCard')) {
                return;
            }
            
            // Get the card id from the event
            var cardId = evt.currentTarget.id;
            
            // Make the server call
            this.ajaxcall("/flapjacksandsasquatches/flapjacksandsasquatches/playCard.html", {
                lock: true,
                card_id: cardId
            }, this, function(result) {
                // Success callback
            });
        },
        
        notif_cardPlayed: function(notif) {
            console.log('notif_cardPlayed', notif);
            
            // Remove card from player's hand
            if (this.playerHand) {
                this.playerHand.removeFromStockById(notif.args.card_id);
            }
            
            // Add card to appropriate area based on type
            // TODO: Implement based on card type
        },
        
        notif_cardDrawn: function(notif) {
            console.log('notif_cardDrawn', notif);
            
            // Add card to player's hand
            if (this.playerHand) {
                this.playerHand.addToStockWithId(notif.args.card_id, notif.args.card_id);
            }
        },
        
        notif_treeChopped: function(notif) {
            console.log('notif_treeChopped', notif);
            
            // Add chop token to tree
            // TODO: Implement chop token visualization
        }
   });             
});
