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

        // Show skip turn indicator if applicable
        if (player.skipNextTurn == 1) {
          this.setSkipTurnIndicator(player_id, true);
        }
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
            card_type_arg: activeTree.card_type_arg,
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

      // Display help cards for all players
      if (gamedatas.helpCards) {
        for (var i in gamedatas.helpCards) {
          this.displayHelp(
            gamedatas.helpCards[i].player_id,
            gamedatas.helpCards[i],
          );
        }
      }

      // Display modifiers for all players
      if (gamedatas.modifiers) {
        for (var i in gamedatas.modifiers) {
          this.displayModifier(
            gamedatas.modifiers[i].player_id,
            gamedatas.modifiers[i],
          );
        }
      }

      // Display cut trees for all players
      if (gamedatas.cutTrees) {
        for (var i in gamedatas.cutTrees) {
          this.displayCutTree(
            gamedatas.cutTrees[i].player_id,
            gamedatas.cutTrees[i],
          );
        }
      }
    },

    addCardToHand: function (card) {
      if (!this.playerHand) return;

      // type_arg is the material definition key (index into jackCards)
      var cardDefId = card.type_arg;
      var cardId = card.id;

      // Add card type if not already registered
      if (!this.playerHand.item_type[cardDefId]) {
        var spriteUrl = g_gamethemeurl + "img/red_cards.jpg";

        // Get sprite position from card definition
        var cardDef = this.gamedatas.jackCards[cardDefId];
        var spritePosition = cardDef ? cardDef.sprite_position || 0 : 0;

        // Weight groups cards by type, then sorts by ID within each group
        var typeWeights = {
          equipment: 100,
          plus_minus: 200,
          help: 300,
          action: 400,
          sasquatch: 500,
          reaction: 600,
        };
        var weight = (cardDef ? typeWeights[cardDef.type] || 0 : 0) + cardDefId;

        this.playerHand.addItemType(
          cardDefId,
          weight,
          spriteUrl,
          spritePosition,
        );
      }

      // Add card to hand
      this.playerHand.addToStockWithId(cardDefId, cardId);
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
      var treeDef = tree.card_type_arg
        ? this.gamedatas.treeCards[tree.card_type_arg]
        : null;

      if (!treeDef) {
        console.error(
          "Tree definition not found for card_type_arg: " + tree.card_type_arg,
        );
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
        '<div class="tree_def_overlay">' +
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

    displayCutTree: function (player_id, cutTree) {
      var area = $("cut_pile_" + player_id);
      if (!area) return;

      var treeDef = this.gamedatas.treeCards[cutTree.card_type_arg];
      if (!treeDef) return;

      var spritePosition = treeDef.sprite_position || 0;
      var cardsPerRow = 4;
      var col = spritePosition % cardsPerRow;
      var row = Math.floor(spritePosition / cardsPerRow);
      var totalRows = 2;

      var bgPosXPct = cardsPerRow > 1 ? (col * 100) / (cardsPerRow - 1) : 0;
      var bgPosYPct = totalRows > 1 ? (row * 100) / (totalRows - 1) : 0;
      var bgSizeWidth = cardsPerRow * this.cardWidth;

      var html =
        '<div class="play_area_card" id="cut_tree_' +
        (cutTree.cut_tree_id || cutTree.card_type_arg + "_" + player_id) +
        '" style="' +
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
        'px auto;">' +
        '<div class="card_label">' +
        treeDef.name +
        " (" +
        (cutTree.points_value || treeDef.points) +
        " pts)</div></div>";

      dojo.place(html, area);
    },

    /**
     * Create a card element for display in a play area (equipment, help, modifier).
     * Returns the HTML string for the card.
     */
    createPlayAreaCard: function (cardDefId, cardId, label) {
      var cardDef = this.gamedatas.jackCards[cardDefId];
      if (!cardDef) return "";

      var spritePosition = cardDef.sprite_position || 0;
      var cardsPerRow = 9; // red cards sprite sheet

      var col = spritePosition % cardsPerRow;
      var row = Math.floor(spritePosition / cardsPerRow);
      var totalRows = 5; // red cards sprite sheet has 5 rows (rows 0-4)

      var bgPosXPct = cardsPerRow > 1 ? (col * 100) / (cardsPerRow - 1) : 0;
      var bgPosYPct = totalRows > 1 ? (row * 100) / (totalRows - 1) : 0;
      var bgSizeWidth = cardsPerRow * this.cardWidth;

      return (
        '<div class="play_area_card" id="play_card_' +
        cardId +
        '" style="' +
        "width: " +
        this.cardWidth +
        "px; " +
        "height: " +
        this.cardHeight +
        "px; " +
        "background-image: url(" +
        g_gamethemeurl +
        "img/red_cards.jpg); " +
        "background-position: " +
        bgPosXPct +
        "% " +
        bgPosYPct +
        "%; " +
        "background-size: " +
        bgSizeWidth +
        "px auto;" +
        '">' +
        '<div class="card_label">' +
        label +
        "</div>" +
        "</div>"
      );
    },

    displayEquipment: function (player_id, equipment) {
      var area = $("equipment_area_" + player_id);
      if (!area) return;

      // Get card definition id from the card table
      var cardDefId = equipment.card_type_arg;
      if (!cardDefId) {
        // If not provided, look it up from gamedatas
        var card = this.gamedatas.cards
          ? this.gamedatas.cards[equipment.card_id]
          : null;
        if (card) cardDefId = card.type_arg;
      }

      var cardDef = cardDefId ? this.gamedatas.jackCards[cardDefId] : null;
      var label = cardDef ? cardDef.name : equipment.equipment_type;

      var html = this.createPlayAreaCard(cardDefId, equipment.card_id, label);
      if (html) {
        dojo.place(html, area);
      }
    },

    displayHelp: function (player_id, helpCard) {
      var area = $("help_area_" + player_id);
      if (!area) return;

      var cardDefId = helpCard.card_type_arg;
      if (!cardDefId) {
        var card = this.gamedatas.cards
          ? this.gamedatas.cards[helpCard.card_id]
          : null;
        if (card) cardDefId = card.type_arg;
      }

      var cardDef = cardDefId ? this.gamedatas.jackCards[cardDefId] : null;
      var label = cardDef ? cardDef.name : helpCard.help_type;

      var html = this.createPlayAreaCard(cardDefId, helpCard.card_id, label);
      if (html) {
        dojo.place(html, area);
      }
    },

    displayModifier: function (player_id, modifier) {
      var area = $("modifier_area_" + player_id);
      if (!area) return;

      var cardDefId = modifier.card_type_arg;
      if (!cardDefId) {
        var card = this.gamedatas.cards
          ? this.gamedatas.cards[modifier.card_id]
          : null;
        if (card) cardDefId = card.type_arg;
      }

      var cardDef = cardDefId ? this.gamedatas.jackCards[cardDefId] : null;
      var label = cardDef
        ? cardDef.name +
          " (" +
          (modifier.modifier_value > 0 ? "+" : "") +
          modifier.modifier_value +
          ")"
        : modifier.modifier_type;

      var html = this.createPlayAreaCard(cardDefId, modifier.card_id, label);
      if (html) {
        dojo.place(html, area);
      }
    },

    removePlayAreaCard: function (card_id) {
      var cardEl = $("play_card_" + card_id);
      if (cardEl) {
        dojo.destroy(cardEl);
      }
    },

    setSkipTurnIndicator: function (player_id, active) {
      var indicator = $("skip_indicator_" + player_id);
      if (!indicator) return;
      if (active) {
        indicator.innerHTML = _("Skips Next Turn");
        dojo.addClass(indicator, "active");
      } else {
        dojo.removeClass(indicator, "active");
      }
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

        case "selectTreesToSwitch":
          this.onEnteringSelectTreesToSwitch(args.args);
          break;

        case "choppingRollResult":
          this.onEnteringChoppingRollResult(args.args);
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

      // Make valid target players clickable (based on targeting mode from server)
      var validTargets = args.valid_targets || [];
      for (var i = 0; i < validTargets.length; i++) {
        var pid = validTargets[i];
        var playerArea = $("player_" + pid + "_area");
        if (playerArea) {
          dojo.addClass(playerArea, "selectable");
          this.connect(playerArea, "onclick", "onPlayerSelected");
        }
      }
    },

    onEnteringSelectTreesToSwitch: function (args) {
      if (!this.isCurrentPlayerActive()) return;

      this.switchTagsSelection = { myTreeId: null, targetTreeId: null };
      var self = this;

      // Make player's cut trees selectable
      var myTrees = args.my_trees || [];
      for (var i = 0; i < myTrees.length; i++) {
        var tree = myTrees[i];
        var el = $("cut_tree_" + tree.cut_tree_id);
        if (el) {
          dojo.addClass(el, "selectable");
          (function (treeId) {
            self.connect(el, "onclick", function () {
              self.onSelectSwitchTree("my", treeId);
            });
          })(tree.cut_tree_id);
        }
      }

      // Make target's cut trees selectable
      var targetTrees = args.target_trees || [];
      for (var i = 0; i < targetTrees.length; i++) {
        var tree = targetTrees[i];
        var el = $("cut_tree_" + tree.cut_tree_id);
        if (el) {
          dojo.addClass(el, "selectable");
          (function (treeId) {
            self.connect(el, "onclick", function () {
              self.onSelectSwitchTree("target", treeId);
            });
          })(tree.cut_tree_id);
        }
      }
    },

    onSelectSwitchTree: function (side, cutTreeId) {
      if (!this.checkAction("actSelectTreesToSwitch")) return;

      // Deselect previous selection on this side
      var prevId =
        side === "my"
          ? this.switchTagsSelection.myTreeId
          : this.switchTagsSelection.targetTreeId;
      if (prevId) {
        var prevEl = $("cut_tree_" + prevId);
        if (prevEl) dojo.removeClass(prevEl, "selected");
      }

      // Select the new tree
      if (side === "my") {
        this.switchTagsSelection.myTreeId = cutTreeId;
      } else {
        this.switchTagsSelection.targetTreeId = cutTreeId;
      }

      var el = $("cut_tree_" + cutTreeId);
      if (el) dojo.addClass(el, "selected");

      // Enable/disable confirm button
      var btn = $("button_confirm_switch");
      if (btn) {
        if (
          this.switchTagsSelection.myTreeId &&
          this.switchTagsSelection.targetTreeId
        ) {
          dojo.removeClass(btn, "disabled");
        } else {
          dojo.addClass(btn, "disabled");
        }
      }
    },

    onConfirmSwitchTags: function () {
      if (!this.checkAction("actSelectTreesToSwitch")) return;

      if (
        !this.switchTagsSelection.myTreeId ||
        !this.switchTagsSelection.targetTreeId
      ) {
        this.showMessage(
          _("You must select one tree from each cut pile"),
          "error",
        );
        return;
      }

      this.bgaPerformAction("actSelectTreesToSwitch", {
        my_cut_tree_id: this.switchTagsSelection.myTreeId,
        target_cut_tree_id: this.switchTagsSelection.targetTreeId,
      });
    },

    onEnteringReactionWindow: function (args) {
      // Show "you" instead of the player's own name when they are the target
      if (args.target_id == this.player_id) {
        args.target_desc = _(" on you");
      }

      if (!this.checkPossibleActions("actPlayReaction")) return;

      // Current player can play a reaction or pass
      if (this.playerHand) {
        this.playerHand.setSelectionMode(1);
      }
    },

    onEnteringChoppingRollResult: function (args) {
      if (!this.isCurrentPlayerActive()) return;

      var html = '<div class="chop_roll_results">';

      // Dice results
      var diceResults = args.dice_results || [];
      if (diceResults.length > 0) {
        html += "<h3>" + _("Chopping Roll") + "</h3>";
        html += '<div class="dice_row">';
        for (var i = 0; i < diceResults.length; i++) {
          html += this.formatDieResult(diceResults[i]);
        }
        html += "</div>";
      }

      // Apprentice roll
      if (args.apprentice_die !== null && args.apprentice_die !== undefined) {
        var apprenticeText = args.apprentice_chop ? _("Chop!") : _("No chop");
        html +=
          '<div class="apprentice_row">' +
          _("Apprentice") +
          ": " +
          this.formatDieResult(args.apprentice_die) +
          " &mdash; " +
          apprenticeText +
          "</div>";
      }

      // Summary
      html +=
        '<div class="roll_summary">' +
        (args.chops || 0) +
        " " +
        _("Chop(s)") +
        ", " +
        (args.breaks || 0) +
        " " +
        _("Break(s)") +
        ", " +
        (args.misses || 0) +
        " " +
        _("Miss(es)") +
        "</div>";

      // Warnings
      if (args.axe_broke) {
        html += '<div class="roll_warning">' + _("Your axe breaks!") + "</div>";
      }
      if (args.long_saw_failed) {
        html +=
          '<div class="roll_warning">' +
          _("Long Saw breaks! It passes to the next player.") +
          "</div>";
      }

      // Continue button inside the modal
      html +=
        '<div class="chop_roll_continue">' +
        '<a href="#" id="button_modal_continue" class="bgabutton bgabutton_blue">' +
        _("Continue") +
        "</a></div>";

      html += "</div>";

      // Create and show the modal
      this.chopResultDialog = new ebg.popindialog();
      this.chopResultDialog.create("chopResultDialog");
      this.chopResultDialog.setTitle(_("Chopping Roll Results"));
      this.chopResultDialog.setMaxWidth(400);
      this.chopResultDialog.setContent(html);
      this.chopResultDialog.hideCloseIcon();
      this.chopResultDialog.show();

      // Connect the modal's continue button
      dojo.connect(
        $("button_modal_continue"),
        "onclick",
        this,
        "onConfirmChopResult",
      );
    },

    formatDieResult: function (value) {
      var cls = "die_miss";
      if (value >= 4) {
        cls = "die_chop";
      } else if (value <= 2) {
        cls = "die_break";
      }
      return '<span class="die_result ' + cls + '">' + value + "</span>";
    },

    onLeavingState: function (stateName) {
      console.log("Leaving state: " + stateName);

      switch (stateName) {
        case "selectTarget":
          this.onLeavingSelectTarget();
          break;

        case "selectTreesToSwitch":
          this.onLeavingSelectTreesToSwitch();
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

    onLeavingSelectTreesToSwitch: function () {
      // Remove selectable/selected classes from all cut tree elements
      dojo.query(".play_area_card.selectable").forEach(function (el) {
        dojo.removeClass(el, "selectable");
        dojo.removeClass(el, "selected");
      });
      this.switchTagsSelection = null;
    },

    onUpdateActionButtons: function (stateName, args) {
      console.log("onUpdateActionButtons: " + stateName);

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case "playerTurn":
            // Play/Discard buttons are added dynamically when a card is selected
            // in onPlayerHandSelectionChanged
            break;

          case "selectTreesToSwitch":
            this.addActionButton(
              "button_confirm_switch",
              _("Confirm Switch"),
              "onConfirmSwitchTags",
            );
            dojo.addClass("button_confirm_switch", "disabled");
            break;

          case "reactionWindow":
            this.addActionButton(
              "button_pass_reaction",
              _("Pass"),
              "onPassReaction",
            );
            break;

          case "sasquatchSighting":
            this.addActionButton(
              "button_roll_save",
              _("Roll to avoid Sasquatch"),
              "onRollSave",
            );
            break;

          case "choppingRoll":
            var diceText =
              _("Roll Dice") +
              " (" +
              args.total_dice +
              " " +
              (args.total_dice === 1 ? _("die") : _("dice"));
            if (args.has_apprentice) {
              diceText += " + " + _("Apprentice");
            }
            diceText += ")";
            this.addActionButton("button_chop_roll", diceText, "onChopRoll");
            break;

          case "choppingRollResult":
            // Continue button is inside the modal
            break;
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    onPlayerHandSelectionChanged: function () {
      var items = this.playerHand.getSelectedItems();

      // Remove any previously added card action buttons
      dojo.destroy("button_play_card");
      dojo.destroy("button_discard");
      dojo.destroy("button_play_reaction");

      if (items.length > 0 && this.checkAction("actPlayCard", true)) {
        var card_id = items[0].id;

        // Show action buttons for the selected card
        this.addActionButton(
          "button_play_card",
          _("Play Card"),
          function () {
            this.onPlayCard(card_id);
          }.bind(this),
        );
        this.addActionButton(
          "button_discard",
          _("Discard"),
          function () {
            this.onDiscardCard(card_id);
          }.bind(this),
          null,
          false,
          "red",
        );
      } else if (
        items.length > 0 &&
        this.checkAction("actPlayReaction", true)
      ) {
        var card_id = items[0].id;

        this.addActionButton(
          "button_play_reaction",
          _("Play Reaction"),
          function () {
            this.onPlayReaction(card_id);
          }.bind(this),
        );
      }
    },

    ///////////////////////////////////////////////////
    //// Player's action

    onPlayCard: function (card_id) {
      console.log("onPlayCard: " + card_id);

      if (!this.checkAction("actPlayCard")) {
        return;
      }

      // Deselect the card
      this.playerHand.unselectAll();

      this.bgaPerformAction("actPlayCard", {
        card_id: card_id,
      });
    },

    onDiscardCard: function (card_id) {
      console.log("onDiscardCard: " + card_id);

      if (!this.checkAction("actDiscardCard")) {
        return;
      }

      // Deselect the card
      this.playerHand.unselectAll();

      this.bgaPerformAction("actDiscardCard", {
        card_id: card_id,
      });
    },

    onPlayerSelected: function (evt) {
      console.log("onPlayerSelected");

      dojo.stopEvent(evt);

      if (!this.checkAction("actSelectTarget")) {
        return;
      }

      // Extract player ID from the clicked element
      var target_id = evt.currentTarget.id
        .replace("player_", "")
        .replace("_area", "");

      this.bgaPerformAction("actSelectTarget", {
        target_id: target_id,
      });
    },

    onPlayReaction: function (card_id) {
      console.log("onPlayReaction: " + card_id);

      if (!this.checkAction("actPlayReaction")) {
        return;
      }

      this.playerHand.unselectAll();

      this.bgaPerformAction("actPlayReaction", {
        card_id: card_id,
      });
    },

    onPassReaction: function (evt) {
      console.log("onPassReaction");

      dojo.stopEvent(evt);

      if (!this.checkAction("actPassReaction")) {
        return;
      }

      this.bgaPerformAction("actPassReaction");
    },

    onRollSave: function (evt) {
      console.log("onRollSave");

      dojo.stopEvent(evt);

      if (!this.checkAction("actRollSave")) {
        return;
      }

      this.bgaPerformAction("actRollSave");
    },

    onChopRoll: function (evt) {
      console.log("onChopRoll");
      dojo.stopEvent(evt);

      if (!this.checkAction("actChopRoll")) {
        return;
      }

      this.bgaPerformAction("actChopRoll");
    },

    onConfirmChopResult: function (evt) {
      console.log("onConfirmChopResult");
      if (evt) dojo.stopEvent(evt);

      if (!this.checkAction("actConfirmChopResult")) {
        return;
      }

      // Close the modal if it exists
      if (this.chopResultDialog) {
        this.chopResultDialog.destroy();
        this.chopResultDialog = null;
      }

      this.bgaPerformAction("actConfirmChopResult");
    },

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    setupNotifications: function () {
      console.log("notifications subscriptions setup");

      // Card notifications
      dojo.subscribe("cardPlayed", this, "notif_cardPlayed");
      dojo.subscribe("cardDiscarded", this, "notif_cardDiscarded");
      dojo.subscribe("cardDrawn", this, "notif_cardDrawn");
      dojo.subscribe("cardReturned", this, "notif_cardReturned");

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

      // Card effect notifications
      dojo.subscribe("equipmentPlaced", this, "notif_equipmentPlaced");
      dojo.subscribe("equipmentDiscarded", this, "notif_equipmentDiscarded");
      dojo.subscribe("equipmentStolen", this, "notif_equipmentStolen");
      dojo.subscribe("modifierApplied", this, "notif_modifierApplied");
      dojo.subscribe("modifierPrevented", this, "notif_modifierPrevented");
      dojo.subscribe("helpPlaced", this, "notif_helpPlaced");
      dojo.subscribe("helpDiscarded", this, "notif_helpDiscarded");
      dojo.subscribe("helpStolen", this, "notif_helpStolen");
      dojo.subscribe("axeBreakFailed", this, "notif_axeBreakFailed");
      dojo.subscribe("forestFire", this, "notif_forestFire");
      dojo.subscribe("switchTags", this, "notif_switchTags");
      dojo.subscribe("treeHugger", this, "notif_treeHugger");
      dojo.subscribe("sasquatchMating", this, "notif_sasquatchMating");
      dojo.subscribe(
        "sasquatchSightingRoll",
        this,
        "notif_sasquatchSightingRoll",
      );

      // Chopping roll notifications
      dojo.subscribe("choppingRoll", this, "notif_choppingRoll");
      dojo.subscribe("choppingSkipped", this, "notif_choppingSkipped");
      dojo.subscribe("apprenticeRoll", this, "notif_apprenticeRoll");
      dojo.subscribe("modifierRemoved", this, "notif_modifierRemoved");
      dojo.subscribe("playerSkipped", this, "notif_playerSkipped");
      dojo.subscribe("cardBlocked", this, "notif_cardBlocked");
    },

    notif_cardPlayed: function (notif) {
      console.log("notif_cardPlayed", notif);

      // Remove card from hand if it belongs to the player viewing this client
      if (notif.args.player_id == this.getActivePlayerId() && this.playerHand) {
        this.playerHand.removeFromStockById(notif.args.card_id);
      }

      // TODO: Display card in play area based on type
    },

    notif_cardDiscarded: function (notif) {
      console.log("notif_cardDiscarded", notif);

      // Remove card from hand if it belongs to the player viewing this client
      if (notif.args.player_id == this.getActivePlayerId() && this.playerHand) {
        this.playerHand.removeFromStockById(notif.args.card_id);
      }
    },

    notif_cardDrawn: function (notif) {
      console.log("notif_cardDrawn", notif);

      // This is a private notification (notifyPlayer), so it's always for us
      if (this.playerHand) {
        this.addCardToHand(notif.args.card);
      }
    },

    notif_cardReturned: function (notif) {
      console.log("notif_cardReturned", notif);

      // Return the card to the active player's hand
      if (notif.args.player_id == this.player_id && this.playerHand) {
        this.addCardToHand({
          id: notif.args.card_id,
          type_arg: notif.args.card_type_arg,
        });
      }

      this.showMessage(
        notif.args.card_name +
          _(" has no valid targets and was returned to hand."),
        "info",
      );
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

      // Remove card from hand if it belongs to the player viewing this client
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
        card_type_arg: notif.args.card_type_arg,
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

      // Add tree to cut pile
      if (notif.args.card_type_arg) {
        this.displayCutTree(notif.args.player_id, {
          card_type_arg: notif.args.card_type_arg,
          points_value: notif.args.points,
          cut_tree_id: notif.args.tree_id,
        });
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

    notif_equipmentPlaced: function (notif) {
      console.log("notif_equipmentPlaced", notif);
      this.displayEquipment(notif.args.player_id, {
        card_id: notif.args.card_id,
        card_type_arg: notif.args.card_type_arg,
        equipment_type: notif.args.equipment_type,
      });
    },

    notif_equipmentDiscarded: function (notif) {
      console.log("notif_equipmentDiscarded", notif);
      this.removePlayAreaCard(notif.args.card_id);
    },

    notif_modifierApplied: function (notif) {
      console.log("notif_modifierApplied", notif);
      this.displayModifier(notif.args.player_id, {
        card_id: notif.args.card_id,
        card_type_arg: notif.args.card_type_arg,
        modifier_value: notif.args.modifier_value,
      });
    },

    notif_modifierPrevented: function (notif) {
      console.log("notif_modifierPrevented", notif);
      // Card was prevented, nothing to display — the log message is sufficient
    },

    notif_helpPlaced: function (notif) {
      console.log("notif_helpPlaced", notif);
      this.displayHelp(notif.args.player_id, {
        card_id: notif.args.card_id,
        card_type_arg: notif.args.card_type_arg,
        help_type: notif.args.help_type,
      });
    },

    notif_helpDiscarded: function (notif) {
      console.log("notif_helpDiscarded", notif);
      this.removePlayAreaCard(notif.args.card_id);
    },

    notif_helpStolen: function (notif) {
      console.log("notif_helpStolen", notif);
      // Remove from original owner's area, add to receiver's area
      // Convention: player_id = receiver, target_id = original owner
      this.removePlayAreaCard(notif.args.card_id);
      this.displayHelp(notif.args.player_id, {
        card_id: notif.args.card_id,
        card_type_arg: notif.args.card_type_arg,
        help_type: notif.args.help_type,
      });
    },

    notif_equipmentStolen: function (notif) {
      console.log("notif_equipmentStolen", notif);
      // Remove stolen card from target's area
      this.removePlayAreaCard(notif.args.card_id);
      // Remove receiver's old equipment of same type if it was replaced
      if (notif.args.replaced_card_id) {
        this.removePlayAreaCard(notif.args.replaced_card_id);
      }
      // Add stolen card to receiver's area
      this.displayEquipment(notif.args.player_id, {
        card_id: notif.args.card_id,
        card_type_arg: notif.args.card_type_arg,
        equipment_type: notif.args.equipment_type,
      });
    },

    notif_axeBreakFailed: function (notif) {
      console.log("notif_axeBreakFailed", notif);
      // TODO: Show failure message/animation
    },

    notif_forestFire: function (notif) {
      console.log("notif_forestFire", notif);
      // TODO: Remove all trees from active areas
    },

    notif_switchTags: function (notif) {
      console.log("notif_switchTags", notif);

      // Remove the swapped cut trees from their original owners
      if (notif.args.player_tree) {
        var el = $("cut_tree_" + notif.args.player_tree.cut_tree_id);
        if (el) dojo.destroy(el);
      }
      if (notif.args.target_tree) {
        var el = $("cut_tree_" + notif.args.target_tree.cut_tree_id);
        if (el) dojo.destroy(el);
      }

      // Display player's old tree on target's cut pile
      if (notif.args.player_tree) {
        this.displayCutTree(notif.args.target_id, {
          card_type_arg: notif.args.player_tree.card_type_arg,
          points_value: notif.args.player_tree.points_value,
          cut_tree_id: notif.args.player_tree.cut_tree_id,
        });
      }

      // Display target's old tree on player's cut pile
      if (notif.args.target_tree) {
        this.displayCutTree(notif.args.player_id, {
          card_type_arg: notif.args.target_tree.card_type_arg,
          points_value: notif.args.target_tree.points_value,
          cut_tree_id: notif.args.target_tree.cut_tree_id,
        });
      }

      // Update scores
      if (this["scoreCounter_" + notif.args.player_id]) {
        this["scoreCounter_" + notif.args.player_id].toValue(
          notif.args.player_new_score,
        );
      }
      if (this["scoreCounter_" + notif.args.target_id]) {
        this["scoreCounter_" + notif.args.target_id].toValue(
          notif.args.target_new_score,
        );
      }
    },

    notif_treeHugger: function (notif) {
      console.log("notif_treeHugger", notif);
      this.setSkipTurnIndicator(notif.args.player_id, true);
    },

    notif_sasquatchMating: function (notif) {
      console.log("notif_sasquatchMating", notif);
      this.setSkipTurnIndicator(notif.args.player_id, true);

      // Clear target's tree area
      var targetTreeArea = $("tree_area_" + notif.args.player_id);
      if (targetTreeArea) {
        dojo.empty(targetTreeArea);
      }

      // Display stolen tree on active player's area
      if (notif.args.tree) {
        var tree = {
          id: notif.args.tree.tree_id,
          card_id: notif.args.tree.card_id,
          card_type_arg: notif.args.tree.card_type_arg,
          type: notif.args.tree.tree_type,
          chops_required: notif.args.tree.chops_required,
          chops_current: notif.args.tree.chop_count,
          points: notif.args.tree.points_value,
        };
        this.displayTree(notif.args.active_id, tree);
      }
    },

    notif_sasquatchSightingRoll: function (notif) {
      console.log("notif_sasquatchSightingRoll", notif);
      // TODO: Show dice roll animation and result
      if (notif.args.loses_turn) {
        this.setSkipTurnIndicator(notif.args.player_id, true);
      }
    },

    notif_choppingRoll: function (notif) {
      console.log("notif_choppingRoll", notif);
      // Update chop progress on the tree
      this.updateChopProgress(notif.args.player_id, notif.args.new_chop_count);
    },

    notif_choppingSkipped: function (notif) {
      console.log("notif_choppingSkipped", notif);
    },

    notif_apprenticeRoll: function (notif) {
      console.log("notif_apprenticeRoll", notif);
      // Update chop progress if the apprentice scored a chop
      if (notif.args.is_chop) {
        this.updateChopProgress(
          notif.args.player_id,
          notif.args.new_chop_count,
        );
      }
    },

    notif_modifierRemoved: function (notif) {
      console.log("notif_modifierRemoved", notif);
      this.removePlayAreaCard(notif.args.card_id);
    },

    notif_playerSkipped: function (notif) {
      console.log("notif_playerSkipped", notif);
      this.setSkipTurnIndicator(notif.args.player_id, false);
    },

    notif_cardBlocked: function (notif) {
      console.log("notif_cardBlocked", notif);
    },
  });
});
