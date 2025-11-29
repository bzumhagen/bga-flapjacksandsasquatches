<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FlapjacksAndSasquatches implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * flapjacksandsasquatches.action.php
 *
 * FlapjacksAndSasquatches main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/flapjacksandsasquatches/flapjacksandsasquatches/myAction.html", ...)
 *
 */


  class action_flapjacksandsasquatches extends APP_GameAction
  {
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "flapjacksandsasquatches_flapjacksandsasquatches";
            self::trace( "Complete reinitialization of board game" );
      }
  	}

    /**
     * Play a card from hand
     */
    public function playCard()
    {
        self::setAjaxMode();

        $card_id = self::getArg( "card_id", AT_posint, true );

        $this->game->playCard( $card_id );

        self::ajaxResponse();
    }

    /**
     * Discard a card from hand
     */
    public function discardCard()
    {
        self::setAjaxMode();

        $card_id = self::getArg( "card_id", AT_posint, true );

        $this->game->discardCard( $card_id );

        self::ajaxResponse();
    }

    /**
     * Select a target for a card effect
     */
    public function selectTarget()
    {
        self::setAjaxMode();

        $target_id = self::getArg( "target_id", AT_posint, true );

        $this->game->selectTarget( $target_id );

        self::ajaxResponse();
    }

    /**
     * Play a reaction card
     */
    public function playReaction()
    {
        self::setAjaxMode();

        $card_id = self::getArg( "card_id", AT_posint, true );

        $this->game->playReaction( $card_id );

        self::ajaxResponse();
    }

    /**
     * Pass on playing a reaction card
     */
    public function passReaction()
    {
        self::setAjaxMode();

        $this->game->passReaction();

        self::ajaxResponse();
    }

  }
