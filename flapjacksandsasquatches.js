/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FlapjacksAndSasquatches implementation : © Benjamin Zumhagen bzumhagen@gmail.com
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
  "dojo",
  "dojo/_base/declare",
  "ebg/core/gamegui",
  "ebg/counter",
  "ebg/stock",
], function (dojo, declare) {
  return declare("bgagame.flapjacksandsasquatches", ebg.core.gamegui, {
    constructor: function () {
      console.log("flapjacksandsasquatches constructor");

      // Card dimensions (display size - actual sprites are 358.44 x 500)
      this.cardWidth = 120;
      this.cardHeight = 167;

      // Stock objects for different card areas
      this.playerHand = null;
      this.equipmentStocks = {};
      this.modifierStocks = {};
      this.helpStocks = {};

      // Track current game state
      this.pendingTarget = null;
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

    setup: function (gamedatas) {
      console.log("Starting game setup");
      console.log(gamedatas);

      // Store game data
      this.gamedatas = gamedatas;

      // Setup player boards
      for (var player_id in gamedatas.players) {
        var player = gamedatas.players[player_id];

        // Setup score counter for this player
        this["scoreCounter_" + player_id] = new ebg.counter();
        this["scoreCounter_" + player_id].create("player_score_" + player_id);
        this["scoreCounter_" + player_id].setValue(player.score);
      }

      // Setup card stocks
      this.setupStocks();

      // Setup deck displays
      this.setupDecks();

      // Display initial game state
      this.updateDisplay(gamedatas);

      // Setup game notifications
      this.setupNotifications();

      console.log("Ending game setup");
    },

    setupStocks: function () {
      // Setup current player's hand
      this.playerHand = new ebg.stock();
      this.playerHand.create(
        this,
        $("myhand"),
        this.cardWidth,
        this.cardHeight,
      );

      // Red card sprite has 9 cards per row, each 358.44 x 500 px
      // But we display them at 72 x 96 px
      this.playerHand.image_items_per_row = 9;

      // Set selection properties
      this.playerHand.setSelectionMode(1);
      this.playerHand.setSelectionAppearance("class");

      // Add all red card types to the hand stock
      // Card types will be added dynamically based on material.inc.php
      // We'll use card_type as the type ID and calculate sprite position

      // Connect selection events
      dojo.connect(
        this.playerHand,
        "onChangeSelection",
        this,
        "onPlayerHandSelectionChanged",
      );
    },

    setupDecks: function () {
      // Setup Jack Deck (red cards) card back
      var redBack = this.gamedatas.cardBacks.red_card_back;
      var redCardsPerRow = redBack.cards_per_row;
      var redSpritePosition = redBack.sprite_position;

      // Calculate grid position (same logic as Stock component)
      var redCol = redSpritePosition % redCardsPerRow;
      var redRow = Math.floor(redSpritePosition / redCardsPerRow);

      // Use percentage positioning like Stock component
      // For a 9-card row, position 8 should be at 100% (8 / (9-1) * 100)
      var redBgPosXPct = (redCol * 100) / (redCardsPerRow - 1);
      var redBgPosYPct = redRow * 100;

      // Background size is total sprite sheet width at display scale
      var redBgSizeWidth = redCardsPerRow * this.cardWidth; // 9 * 120 = 1080px

      var jackDeck = $("jack_deck");
      if (jackDeck) {
        dojo.style(jackDeck, {
          backgroundImage:
            "url(" + g_gamethemeurl + "img/" + redBack.sprite + ")",
          backgroundPosition: redBgPosXPct + "% " + redBgPosYPct + "%",
          backgroundSize: redBgSizeWidth + "px auto",
        });
      }

      // Setup Tree Deck card back
      var treeBack = this.gamedatas.cardBacks.tree_card_back;
      var treeCardsPerRow = treeBack.cards_per_row;
      var treeSpritePosition = treeBack.sprite_position;

      // Calculate grid position (same logic as Stock component)
      var treeCol = treeSpritePosition % treeCardsPerRow;
      var treeRow = Math.floor(treeSpritePosition / treeCardsPerRow);

      // Use percentage positioning like Stock component
      // For a 4-card row, position 3 (row 1, col 3) should be at X: 100% (3 / (4-1) * 100), Y: 100%
      var treeBgPosXPct = (treeCol * 100) / (treeCardsPerRow - 1);
      var treeBgPosYPct = treeRow * 100;

      // Background size is total sprite sheet width at display scale
      var treeBgSizeWidth = treeCardsPerRow * this.cardWidth; // 4 * 120 = 480px

      var treeDeck = $("tree_deck");
      if (treeDeck) {
        dojo.style(treeDeck, {
          backgroundImage:
            "url(" + g_gamethemeurl + "img/" + treeBack.sprite + ")",
          backgroundPosition: treeBgPosXPct + "% " + treeBgPosYPct + "%",
          backgroundSize: treeBgSizeWidth + "px auto",
        });
      }
    },

    updateDisplay: function (gamedatas) {
      // Update player hands (only current player's hand is visible)
      if (gamedatas.hand) {
        for (var i in gamedatas.hand) {
          var card = gamedatas.hand[i];
          this.addCardToHand(card);
        }
      }

      // Display active trees for all players
      if (gamedatas.activeTrees) {
        for (var i in gamedatas.activeTrees) {
          var activeTree = gamedatas.activeTrees[i];
          var tree = {
            id: activeTree.tree_id,
            card_id: activeTree.card_id,
            type: activeTree.tree_type,
            chops_required: activeTree.chops_required,
            chops_current: activeTree.chop_count,
            points: activeTree.points_value,
          };
          this.displayTree(activeTree.player_id, tree);
        }
      }

      // Display equipment for all players
      if (gamedatas.equipment) {
        for (var i in gamedatas.equipment) {
          this.displayEquipment(
            gamedatas.equipment[i].player_id,
            gamedatas.equipment[i],
          );
        }
      }
    },

    addCardToHand: function (card) {
      if (!this.playerHand) return;

      // Add card type if not already registered
      if (!this.playerHand.items[card.type]) {
        var spriteUrl = g_gamethemeurl + "img/red_cards.jpg";

        // Get sprite position from card definition
        var cardDef = this.gamedatas.redCards[card.type];
        var spritePosition = cardDef ? cardDef.sprite_position || 0 : 0;

        this.playerHand.addItemType(
          card.type,
          card.type,
          spriteUrl,
          spritePosition,
        );
      }

      // Add card to hand
      this.playerHand.addToStockWithId(card.type, card.id);
    },

    updatePlayerState: function (player_id, state) {
      // Update player's tree display
      if (state.tree) {
        this.displayTree(player_id, state.tree);
      }

      // Update equipment
      if (state.equipment) {
        for (var i in state.equipment) {
          this.displayEquipment(player_id, state.equipment[i]);
        }
      }

      // Update modifiers
      if (state.modifiers) {
        for (var i in state.modifiers) {
          this.displayModifier(player_id, state.modifiers[i]);
        }
      }
    },

    displayTree: function (player_id, tree) {
      var treeArea = $("tree_area_" + player_id);
      if (!treeArea) return;

      // Clear existing tree
      dojo.empty(treeArea);

      // Get tree card definition to find sprite position
      var treeDef = null;
      for (var key in this.gamedatas.treeCards) {
        if (this.gamedatas.treeCards[key].id == tree.id) {
          treeDef = this.gamedatas.treeCards[key];
          break;
        }
      }

      if (!treeDef) {
        console.error("Tree definition not found for tree id: " + tree.id);
        return;
      }

      // Calculate sprite position
      var spritePosition = treeDef.sprite_position || 0;
      var cardsPerRow = 4; // 4 tree cards per row

      // Calculate grid position
      var col = spritePosition % cardsPerRow;
      var row = Math.floor(spritePosition / cardsPerRow);

      // Use percentage positioning like Stock component and deck displays
      var bgPosXPct = (col * 100) / (cardsPerRow - 1);
      var bgPosYPct = row * 100;

      // Background size is total sprite sheet width at display scale
      var bgSizeWidth = cardsPerRow * this.cardWidth; // 4 * 120 = 480px

      // Create tree card visual
      var cardHtml =
        '<div class="tree_card_visual" style="' +
        "width: " +
        this.cardWidth +
        "px; " +
        "height: " +
        this.cardHeight +
        "px; " +
        "background-image: url(" +
        g_gamethemeurl +
        "img/tree_cards.jpg); " +
        "background-position: " +
        bgPosXPct +
        "% " +
        bgPosYPct +
        "%; " +
        "background-size: " +
        bgSizeWidth +
        "px auto;" +
        '"></div>';

      // Create tree info overlay
      var html =
        '<div class="tree_display" id="tree_' +
        player_id +
        '" data-tree-id="' +
        tree.id +
        '">' +
        cardHtml +
        '<div class="tree_info_overlay">' +
        '<div class="tree_name">' +
        treeDef.name +
        "</div>" +
        '<div class="tree_progress">' +
        '<span class="chops_current" id="chops_current_' +
        player_id +
        '">0</span> / ' +
        '<span class="chops_required">' +
        treeDef.chops_required +
        "</span>" +
        "</div>" +
        '<div class="tree_points">Worth: ' +
        treeDef.points +
        " points</div>" +
        '<div class="chop_markers" id="chop_markers_' +
        player_id +
        '"></div>' +
        "</div>" +
        "</div>";

      dojo.place(html, treeArea);

      // Update chop progress
      if (tree.chops_current) {
        this.updateChopProgress(player_id, tree.chops_current);
      }
    },

    updateChopProgress: function (player_id, chops) {
      var chopCounter = $("chops_current_" + player_id);
      if (chopCounter) {
        chopCounter.innerHTML = chops;
      }
    },

    displayEquipment: function (player_id, equipment) {
      // TODO: Display equipment cards in player area
      console.log("Display equipment for player " + player_id, equipment);
    },

    displayModifier: function (player_id, modifier) {
      // TODO: Display modifier cards in player area
      console.log("Display modifier for player " + player_id, modifier);
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    onEnteringState: function (stateName, args) {
      console.log("Entering state: " + stateName);

      switch (stateName) {
        case "playerTurn":
          this.onEnteringPlayerTurn(args.args);
          break;

        case "selectTarget":
          this.onEnteringSelectTarget(args.args);
          break;

        case "reactionWindow":
          this.onEnteringReactionWindow(args.args);
          break;
      }
    },

    onEnteringPlayerTurn: function (args) {
      if (!this.isCurrentPlayerActive()) return;

      // Enable hand selection for playing/discarding
      if (this.playerHand) {
        this.playerHand.setSelectionMode(1);
      }
    },

    onEnteringSelectTarget: function (args) {
      if (!this.isCurrentPlayerActive()) return;

      // Enable player selection for targeting
      this.pendingTarget = true;

      // Make all other players clickable
      for (var player_id in this.gamedatas.players) {
        if (player_id != this.player_id) {
          var playerArea = $("player_" + player_id + "_area");
          if (playerArea) {
            dojo.addClass(playerArea, "selectable");
            this.connect(playerArea, "onclick", "onPlayerSelected");
          }
        }
      }
    },

    onEnteringReactionWindow: function (args) {
      if (!this.checkPossibleActions("playReaction")) return;

      // Current player can play a reaction or pass
      if (this.playerHand) {
        this.playerHand.setSelectionMode(1);
      }
    },

    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      switch (stateName) {
        case "selectTarget":
          this.onLeavingSelectTarget();
          break;

        case "playerTurn":
          if (this.playerHand) {
            this.playerHand.setSelectionMode(0);
          }
          break;
      }
    },

    onLeavingSelectTarget: function () {
      this.pendingTarget = false;

      // Remove selectable class from all players
      for (var player_id in this.gamedatas.players) {
        var playerArea = $("player_" + player_id + "_area");
        if (playerArea) {
          dojo.removeClass(playerArea, "selectable");
        }
      }
    },

    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "playerTurn":
            this.addActionButton(
              "button_discard",
              _("Discard selected card"),
              "onDiscardCard",
            );
            break;

          case "reactionWindow":
            this.addActionButton(
              "button_pass_reaction",
              _("Pass"),
              "onPassReaction",
            );
            break;
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    onPlayerHandSelectionChanged: function () {
      var items = this.playerHand.getSelectedItems();

      if (items.length > 0) {
        // A card was selected
        var card_id = items[0].id;

        if (this.checkAction("playCard", true)) {
          // Play the card
          this.onPlayCard(card_id);
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Player's action

    onPlayCard: function (card_id) {
      console.log("onPlayCard: " + card_id);

      if (!this.checkAction("playCard")) {
        return;
      }

      // Deselect the card
      this.playerHand.unselectAll();

      this.bgaPerformAction("playCard", {
        card_id: card_id,
      });
    },

    onDiscardCard: function (evt) {
      console.log("onDiscardCard");

      dojo.stopEvent(evt);

      if (!this.checkAction("discardCard")) {
        return;
      }

      var items = this.playerHand.getSelectedItems();
      if (items.length == 0) {
        this.showMessage(_("Please select a card to discard"), "error");
        return;
      }

      var card_id = items[0].id;

      // Deselect the card
      this.playerHand.unselectAll();

      this.bgaPerformAction("discardCard", {
        card_id: card_id,
      });
    },

    onPlayerSelected: function (evt) {
      console.log("onPlayerSelected");

      dojo.stopEvent(evt);

      if (!this.checkAction("selectTarget")) {
        return;
      }

      // Extract player ID from the clicked element
      var target_id = evt.currentTarget.id
        .replace("player_", "")
        .replace("_area", "");

      this.bgaPerformAction("selectTarget", {
        target_id: target_id,
      });
    },

    onPassReaction: function (evt) {
      console.log("onPassReaction");

      dojo.stopEvent(evt);

      if (!this.checkAction("passReaction")) {
        return;
      }

      this.bgaPerformAction("passReaction");
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    setupNotifications: function () {
      console.log("notifications subscriptions setup");

      // Card notifications
      dojo.subscribe("cardPlayed", this, "notif_cardPlayed");
      dojo.subscribe("cardDiscarded", this, "notif_cardDiscarded");
      dojo.subscribe("cardDrawn", this, "notif_cardDrawn");

      // Target selection
      dojo.subscribe("targetSelected", this, "notif_targetSelected");

      // Reaction notifications
      dojo.subscribe("reactionPlayed", this, "notif_reactionPlayed");
      dojo.subscribe("reactionPassed", this, "notif_reactionPassed");

      // Tree notifications
      dojo.subscribe("treeDrawn", this, "notif_treeDrawn");
      dojo.subscribe("treeChopped", this, "notif_treeChopped");
      dojo.subscribe("treeCompleted", this, "notif_treeCompleted");

      // Score update
      dojo.subscribe("scoreUpdate", this, "notif_scoreUpdate");
    },

    notif_cardPlayed: function (notif) {
      console.log("notif_cardPlayed", notif);

      // Remove card from current player's hand if it's their card
      if (notif.args.player_id == this.player_id && this.playerHand) {
        this.playerHand.removeFromStockById(notif.args.card_id);
      }

      // TODO: Display card in play area based on type
    },

    notif_cardDiscarded: function (notif) {
      console.log("notif_cardDiscarded", notif);

      // Remove card from current player's hand if it's their card
      if (notif.args.player_id == this.player_id && this.playerHand) {
        this.playerHand.removeFromStockById(notif.args.card_id);
      }
    },

    notif_cardDrawn: function (notif) {
      console.log("notif_cardDrawn", notif);

      // Add card to current player's hand if it's for them
      if (notif.args.player_id == this.player_id && this.playerHand) {
        this.addCardToHand(notif.args.card);
      }
    },

    notif_targetSelected: function (notif) {
      console.log("notif_targetSelected", notif);

      // Highlight the targeted player briefly
      var targetArea = $("player_" + notif.args.target_id + "_area");
      if (targetArea) {
        dojo.addClass(targetArea, "targeted");
        setTimeout(function () {
          dojo.removeClass(targetArea, "targeted");
        }, 2000);
      }
    },

    notif_reactionPlayed: function (notif) {
      console.log("notif_reactionPlayed", notif);

      // Remove card from hand if it's the current player
      if (notif.args.player_id == this.player_id && this.playerHand) {
        this.playerHand.removeFromStockById(notif.args.card_id);
      }
    },

    notif_reactionPassed: function (notif) {
      console.log("notif_reactionPassed", notif);
      // Just a notification, no UI update needed
    },

    notif_treeDrawn: function (notif) {
      console.log("notif_treeDrawn", notif);

      // Construct tree object from notification args
      var tree = {
        id: notif.args.tree_id,
        card_id: notif.args.card_id,
        type: notif.args.tree_type,
        chops_required: notif.args.chops_required,
        chops_current: 0,
        points: notif.args.points,
      };

      // Display the tree for the player
      this.displayTree(notif.args.player_id, tree);
    },

    notif_treeChopped: function (notif) {
      console.log("notif_treeChopped", notif);

      // Update chop progress
      this.updateChopProgress(notif.args.player_id, notif.args.chops_current);
    },

    notif_treeCompleted: function (notif) {
      console.log("notif_treeCompleted", notif);

      // Remove tree from active area
      var treeArea = $("tree_area_" + notif.args.player_id);
      if (treeArea) {
        dojo.empty(treeArea);
      }

      // Update score
      if (this["scoreCounter_" + notif.args.player_id]) {
        this["scoreCounter_" + notif.args.player_id].toValue(
          notif.args.new_score,
        );
      }
    },

    notif_scoreUpdate: function (notif) {
      console.log("notif_scoreUpdate", notif);

      // Update player score
      if (this["scoreCounter_" + notif.args.player_id]) {
        this["scoreCounter_" + notif.args.player_id].toValue(notif.args.score);
      }
    },
  });
});
