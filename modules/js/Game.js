/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FlapjacksAndSasquatches implementation : © Benjamin Zumhagen bzumhagen@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.js
 *
 * FlapjacksAndSasquatches user interface script (modern ESM pattern)
 */

const [Counter, Stock] = await importDojoLibs(["ebg/counter", "ebg/stock"]);

export class Game {
  constructor(bga) {
    this.bga = bga;

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
  }

  setup(gamedatas) {
    console.log("Starting game setup");
    console.log(gamedatas);

    // Store game data
    this.gamedatas = gamedatas;

    // Build main game area HTML
    var playerAreasHtml = "";
    var directions = ["S", "W", "N", "E", "S2", "W2", "N2", "E2"];
    for (var player_id in gamedatas.players) {
      var player = gamedatas.players[player_id];
      var dir = directions.shift();
      playerAreasHtml +=
        '<div id="player_' +
        player_id +
        '_area" class="player_area whiteblock playertable_' +
        dir +
        '">' +
        '<div class="playertablename" style="color:#' +
        player.color +
        '">' +
        player.name +
        '<span id="skip_indicator_' +
        player_id +
        '" class="skip_turn_indicator"></span>' +
        "</div>" +
        '<div class="player_play_area">' +
        '<div class="tree_area" id="tree_area_' +
        player_id +
        '"></div>' +
        '<div class="equipment_area" id="equipment_area_' +
        player_id +
        '"></div>' +
        '<div class="help_area" id="help_area_' +
        player_id +
        '"></div>' +
        '<div class="modifier_area" id="modifier_area_' +
        player_id +
        '"></div>' +
        '<div class="cut_pile" id="cut_pile_' +
        player_id +
        '"></div>' +
        "</div>" +
        "</div>";
    }

    var html =
      '<div id="flapjacksandsasquatches_game_area">' +
      '<div id="center_area" class="whiteblock">' +
      '<div id="tree_deck" class="deck"></div>' +
      '<div id="jack_deck" class="deck"></div>' +
      '<div id="discard_pile" class="deck"></div>' +
      "</div>" +
      '<div id="player_areas">' +
      playerAreasHtml +
      "</div>" +
      '<div id="myhand_wrap" class="whiteblock">' +
      "<h3>" +
      _("My hand") +
      "</h3>" +
      '<div id="myhand"></div>' +
      "</div>" +
      "</div>";

    this.bga.gameArea.getElement().insertAdjacentHTML("beforeend", html);

    // Setup player boards
    for (var player_id in gamedatas.players) {
      var player = gamedatas.players[player_id];

      // Setup score counter for this player
      this["scoreCounter_" + player_id] = new Counter();
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
  }

  setupStocks() {
    // Setup current player's hand
    this.playerHand = new Stock();
    this.playerHand.create(
      this.bga.gameui,
      $("myhand"),
      this.cardWidth,
      this.cardHeight,
    );

    this.playerHand.image_items_per_row = 9;
    this.playerHand.setSelectionMode(1);
    this.playerHand.setSelectionAppearance("class");

    this.playerHand.onChangeSelection = () => {
      this.onPlayerHandSelectionChanged();
    };
  }

  setupDecks() {
    // Setup Jack Deck (red cards) card back
    var redBack = this.gamedatas.cardBacks.red_card_back;
    var redCardsPerRow = redBack.cards_per_row;
    var redSpritePosition = redBack.sprite_position;

    var redCol = redSpritePosition % redCardsPerRow;
    var redRow = Math.floor(redSpritePosition / redCardsPerRow);

    var redBgPosXPct = (redCol * 100) / (redCardsPerRow - 1);
    var redBgPosYPct = redRow * 100;

    var redBgSizeWidth = redCardsPerRow * this.cardWidth;

    var jackDeck = $("jack_deck");
    if (jackDeck) {
      jackDeck.style.backgroundImage =
        "url(" + g_gamethemeurl + "img/" + redBack.sprite + ")";
      jackDeck.style.backgroundPosition =
        redBgPosXPct + "% " + redBgPosYPct + "%";
      jackDeck.style.backgroundSize = redBgSizeWidth + "px auto";
    }

    // Setup Tree Deck card back
    var treeBack = this.gamedatas.cardBacks.tree_card_back;
    var treeCardsPerRow = treeBack.cards_per_row;
    var treeSpritePosition = treeBack.sprite_position;

    var treeCol = treeSpritePosition % treeCardsPerRow;
    var treeRow = Math.floor(treeSpritePosition / treeCardsPerRow);

    var treeBgPosXPct = (treeCol * 100) / (treeCardsPerRow - 1);
    var treeBgPosYPct = treeRow * 100;

    var treeBgSizeWidth = treeCardsPerRow * this.cardWidth;

    var treeDeck = $("tree_deck");
    if (treeDeck) {
      treeDeck.style.backgroundImage =
        "url(" + g_gamethemeurl + "img/" + treeBack.sprite + ")";
      treeDeck.style.backgroundPosition =
        treeBgPosXPct + "% " + treeBgPosYPct + "%";
      treeDeck.style.backgroundSize = treeBgSizeWidth + "px auto";
    }
  }

  updateDisplay(gamedatas) {
    if (gamedatas.hand) {
      for (var i in gamedatas.hand) {
        var card = gamedatas.hand[i];
        this.addCardToHand(card);
      }
    }

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

    if (gamedatas.equipment) {
      for (var i in gamedatas.equipment) {
        this.displayEquipment(
          gamedatas.equipment[i].player_id,
          gamedatas.equipment[i],
        );
      }
    }

    if (gamedatas.helpCards) {
      for (var i in gamedatas.helpCards) {
        this.displayHelp(
          gamedatas.helpCards[i].player_id,
          gamedatas.helpCards[i],
        );
      }
    }

    if (gamedatas.modifiers) {
      for (var i in gamedatas.modifiers) {
        this.displayModifier(
          gamedatas.modifiers[i].player_id,
          gamedatas.modifiers[i],
        );
      }
    }

    if (gamedatas.cutTrees) {
      for (var i in gamedatas.cutTrees) {
        this.displayCutTree(
          gamedatas.cutTrees[i].player_id,
          gamedatas.cutTrees[i],
        );
      }
    }
  }

  addCardToHand(card) {
    if (!this.playerHand) return;

    var cardDefId = card.type_arg;
    var cardId = card.id;

    if (!this.playerHand.item_type[cardDefId]) {
      var spriteUrl = g_gamethemeurl + "img/red_cards.jpg";

      var cardDef = this.gamedatas.jackCards[cardDefId];
      var spritePosition = cardDef ? cardDef.sprite_position || 0 : 0;

      var typeWeights = {
        equipment: 100,
        plus_minus: 200,
        help: 300,
        action: 400,
        sasquatch: 500,
        reaction: 600,
      };
      var weight = (cardDef ? typeWeights[cardDef.type] || 0 : 0) + cardDefId;

      this.playerHand.addItemType(cardDefId, weight, spriteUrl, spritePosition);
    }

    this.playerHand.addToStockWithId(cardDefId, cardId);
  }

  updatePlayerState(player_id, state) {
    if (state.tree) {
      this.displayTree(player_id, state.tree);
    }

    if (state.equipment) {
      for (var i in state.equipment) {
        this.displayEquipment(player_id, state.equipment[i]);
      }
    }

    if (state.modifiers) {
      for (var i in state.modifiers) {
        this.displayModifier(player_id, state.modifiers[i]);
      }
    }
  }

  displayTree(player_id, tree) {
    var treeArea = $("tree_area_" + player_id);
    if (!treeArea) return;

    treeArea.innerHTML = "";

    var treeDef = tree.card_type_arg
      ? this.gamedatas.treeCards[tree.card_type_arg]
      : null;

    if (!treeDef) {
      console.error(
        "Tree definition not found for card_type_arg: " + tree.card_type_arg,
      );
      return;
    }

    var spritePosition = treeDef.sprite_position || 0;
    var cardsPerRow = 4;

    var col = spritePosition % cardsPerRow;
    var row = Math.floor(spritePosition / cardsPerRow);

    var bgPosXPct = (col * 100) / (cardsPerRow - 1);
    var bgPosYPct = row * 100;

    var bgSizeWidth = cardsPerRow * this.cardWidth;

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

    treeArea.insertAdjacentHTML("beforeend", html);

    if (tree.chops_current) {
      this.updateChopProgress(player_id, tree.chops_current);
    }
  }

  updateChopProgress(player_id, chops) {
    var chopCounter = $("chops_current_" + player_id);
    if (chopCounter) {
      chopCounter.innerHTML = chops;
    }
  }

  displayCutTree(player_id, cutTree) {
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

    area.insertAdjacentHTML("beforeend", html);
  }

  createPlayAreaCard(cardDefId, cardId, label) {
    var cardDef = this.gamedatas.jackCards[cardDefId];
    if (!cardDef) return "";

    var spritePosition = cardDef.sprite_position || 0;
    var cardsPerRow = 9;

    var col = spritePosition % cardsPerRow;
    var row = Math.floor(spritePosition / cardsPerRow);
    var totalRows = 5;

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
  }

  displayEquipment(player_id, equipment) {
    var area = $("equipment_area_" + player_id);
    if (!area) return;

    var cardDefId = equipment.card_type_arg;
    if (!cardDefId) {
      var card = this.gamedatas.cards
        ? this.gamedatas.cards[equipment.card_id]
        : null;
      if (card) cardDefId = card.type_arg;
    }

    var cardDef = cardDefId ? this.gamedatas.jackCards[cardDefId] : null;
    var label = cardDef ? cardDef.name : equipment.equipment_type;

    var html = this.createPlayAreaCard(cardDefId, equipment.card_id, label);
    if (html) {
      area.insertAdjacentHTML("beforeend", html);
    }
  }

  displayHelp(player_id, helpCard) {
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
      area.insertAdjacentHTML("beforeend", html);
    }
  }

  displayModifier(player_id, modifier) {
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
      area.insertAdjacentHTML("beforeend", html);
    }
  }

  removePlayAreaCard(card_id) {
    var cardEl = $("play_card_" + card_id);
    if (cardEl) {
      cardEl.remove();
    }
  }

  setSkipTurnIndicator(player_id, active) {
    var indicator = $("skip_indicator_" + player_id);

    if (!indicator) return;
    if (active) {
      indicator.innerHTML = _("Skips Next Turn");
      indicator.classList.add("active");
    } else {
      indicator.classList.remove("active");
    }
  }

  ///////////////////////////////////////////////////
  //// Game & client states

  onEnteringState(stateName, args) {
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
  }

  onEnteringPlayerTurn(args) {
    if (!this.bga.players.isCurrentPlayerActive()) return;

    if (this.playerHand) {
      this.playerHand.setSelectionMode(1);
    }
  }

  onEnteringSelectTarget(args) {
    if (!this.bga.players.isCurrentPlayerActive()) return;

    this.pendingTarget = true;

    var validTargets = args.valid_targets || [];
    for (var i = 0; i < validTargets.length; i++) {
      var pid = validTargets[i];
      var playerArea = $("player_" + pid + "_area");
      if (playerArea) {
        playerArea.classList.add("selectable");
        playerArea.addEventListener("click", (evt) => {
          this.onPlayerSelected(evt);
        });
      }
    }
  }

  onEnteringSelectTreesToSwitch(args) {
    if (!this.bga.players.isCurrentPlayerActive()) return;

    this.switchTagsSelection = { myTreeId: null, targetTreeId: null };

    var myTrees = args.my_trees || [];
    for (var i = 0; i < myTrees.length; i++) {
      var tree = myTrees[i];
      var el = $("cut_tree_" + tree.cut_tree_id);
      if (el) {
        el.classList.add("selectable");
        ((treeId) => {
          el.addEventListener("click", () => {
            this.onSelectSwitchTree("my", treeId);
          });
        })(tree.cut_tree_id);
      }
    }

    var targetTrees = args.target_trees || [];
    for (var i = 0; i < targetTrees.length; i++) {
      var tree = targetTrees[i];
      var el = $("cut_tree_" + tree.cut_tree_id);
      if (el) {
        el.classList.add("selectable");
        ((treeId) => {
          el.addEventListener("click", () => {
            this.onSelectSwitchTree("target", treeId);
          });
        })(tree.cut_tree_id);
      }
    }
  }

  onSelectSwitchTree(side, cutTreeId) {
    if (!this.bga.actions.checkAction("actSelectTreesToSwitch")) return;

    var prevId =
      side === "my"
        ? this.switchTagsSelection.myTreeId
        : this.switchTagsSelection.targetTreeId;
    if (prevId) {
      var prevEl = $("cut_tree_" + prevId);
      if (prevEl) prevEl.classList.remove("selected");
    }

    if (side === "my") {
      this.switchTagsSelection.myTreeId = cutTreeId;
    } else {
      this.switchTagsSelection.targetTreeId = cutTreeId;
    }

    var el = $("cut_tree_" + cutTreeId);
    if (el) el.classList.add("selected");

    var btn = $("button_confirm_switch");
    if (btn) {
      if (
        this.switchTagsSelection.myTreeId &&
        this.switchTagsSelection.targetTreeId
      ) {
        btn.classList.remove("disabled");
      } else {
        btn.classList.add("disabled");
      }
    }
  }

  onConfirmSwitchTags() {
    if (!this.bga.actions.checkAction("actSelectTreesToSwitch")) return;

    if (
      !this.switchTagsSelection.myTreeId ||
      !this.switchTagsSelection.targetTreeId
    ) {
      this.bga.dialogs.showMessage(
        _("You must select one tree from each cut pile"),
        "error",
      );
      return;
    }

    this.bga.actions.performAction("actSelectTreesToSwitch", {
      my_cut_tree_id: this.switchTagsSelection.myTreeId,
      target_cut_tree_id: this.switchTagsSelection.targetTreeId,
    });
  }

  onEnteringReactionWindow(args) {
    if (args.target_id == this.bga.players.getCurrentPlayerId()) {
      args.target_desc = _(" on you");
    }

    if (!this.bga.actions.checkPossibleActions("actPlayReaction")) return;

    if (this.playerHand) {
      this.playerHand.setSelectionMode(1);
    }
  }

  onEnteringChoppingRollResult(args) {
    if (!this.bga.players.isCurrentPlayerActive()) return;

    var html = '<div class="chop_roll_results">';

    var diceResults = args.dice_results || [];
    if (diceResults.length > 0) {
      html += "<h3>" + _("Chopping Roll") + "</h3>";
      html += '<div class="dice_row">';
      for (var i = 0; i < diceResults.length; i++) {
        html += this.formatDieResult(diceResults[i]);
      }
      html += "</div>";
    }

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

    if (args.axe_broke) {
      html += '<div class="roll_warning">' + _("Your axe breaks!") + "</div>";
    }
    if (args.long_saw_failed) {
      html +=
        '<div class="roll_warning">' +
        _("Long Saw breaks! It passes to the next player.") +
        "</div>";
    }

    html +=
      '<div class="chop_roll_continue">' +
      '<a href="#" id="button_modal_continue" class="bgabutton bgabutton_blue">' +
      _("Continue") +
      "</a></div>";

    html += "</div>";

    this.chopResultDialog = new ebg.popindialog();
    this.chopResultDialog.create("chopResultDialog");
    this.chopResultDialog.setTitle(_("Chopping Roll Results"));
    this.chopResultDialog.setMaxWidth(400);
    this.chopResultDialog.setContent(html);
    this.chopResultDialog.hideCloseIcon();
    this.chopResultDialog.show();

    $("button_modal_continue").addEventListener("click", (evt) => {
      this.onConfirmChopResult(evt);
    });
  }

  formatDieResult(value) {
    var cls = "die_miss";
    if (value >= 4) {
      cls = "die_chop";
    } else if (value <= 2) {
      cls = "die_break";
    }
    return '<span class="die_result ' + cls + '">' + value + "</span>";
  }

  onLeavingState(stateName) {
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
  }

  onLeavingSelectTarget() {
    this.pendingTarget = false;

    for (var player_id in this.gamedatas.players) {
      var playerArea = $("player_" + player_id + "_area");
      if (playerArea) {
        playerArea.classList.remove("selectable");
      }
    }
  }

  onLeavingSelectTreesToSwitch() {
    document
      .querySelectorAll(".play_area_card.selectable")
      .forEach(function (el) {
        el.classList.remove("selectable");
        el.classList.remove("selected");
      });
    this.switchTagsSelection = null;
  }

  onUpdateActionButtons(stateName, args) {
    console.log("onUpdateActionButtons: " + stateName);

    if (this.bga.players.isCurrentPlayerActive()) {
      switch (stateName) {
        case "playerTurn":
          break;

        case "selectTreesToSwitch":
          this.bga.statusBar.addActionButton(
            _("Confirm Switch"),
            () => {
              this.onConfirmSwitchTags();
            },
            { id: "button_confirm_switch" },
          );
          $("button_confirm_switch").classList.add("disabled");
          break;

        case "reactionWindow":
          this.bga.statusBar.addActionButton(
            _("Pass"),
            () => {
              this.onPassReaction();
            },
            { id: "button_pass_reaction" },
          );
          break;

        case "chooseTakeTree":
          this.bga.statusBar.addActionButton(
            _("Take Tree"),
            () => {
              this.onTakeTree();
            },
            { id: "button_take_tree" },
          );
          this.bga.statusBar.addActionButton(
            _("No Thanks"),
            () => {
              this.onDeclineTree();
            },
            { id: "button_decline_tree", color: "red" },
          );
          break;

        case "sasquatchSighting":
          this.bga.statusBar.addActionButton(
            _("Roll to avoid Sasquatch"),
            () => {
              this.onRollSave();
            },
            { id: "button_roll_save" },
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
          this.bga.statusBar.addActionButton(
            diceText,
            () => {
              this.onChopRoll();
            },
            { id: "button_chop_roll" },
          );
          break;

        case "choppingRollResult":
          break;
      }
    }
  }

  ///////////////////////////////////////////////////
  //// Utility methods

  onPlayerHandSelectionChanged() {
    var items = this.playerHand.getSelectedItems();

    var btnPlay = $("button_play_card");
    if (btnPlay) btnPlay.remove();
    var btnDiscard = $("button_discard");
    if (btnDiscard) btnDiscard.remove();
    var btnReaction = $("button_play_reaction");
    if (btnReaction) btnReaction.remove();

    if (items.length > 0 && this.bga.actions.checkAction("actPlayCard", true)) {
      var card_id = items[0].id;

      this.bga.statusBar.addActionButton(
        _("Play Card"),
        () => {
          this.onPlayCard(card_id);
        },
        { id: "button_play_card" },
      );
      this.bga.statusBar.addActionButton(
        _("Discard"),
        () => {
          this.onDiscardCard(card_id);
        },
        { id: "button_discard", color: "red" },
      );
    } else if (
      items.length > 0 &&
      this.bga.actions.checkAction("actPlayReaction", true)
    ) {
      var card_id = items[0].id;

      this.bga.statusBar.addActionButton(
        _("Play Reaction"),
        () => {
          this.onPlayReaction(card_id);
        },
        { id: "button_play_reaction" },
      );
    }
  }

  ///////////////////////////////////////////////////
  //// Player's action

  onPlayCard(card_id) {
    console.log("onPlayCard: " + card_id);

    if (!this.bga.actions.checkAction("actPlayCard")) {
      return;
    }

    this.playerHand.unselectAll();

    this.bga.actions.performAction("actPlayCard", {
      card_id: card_id,
    });
  }

  onDiscardCard(card_id) {
    console.log("onDiscardCard: " + card_id);

    if (!this.bga.actions.checkAction("actDiscardCard")) {
      return;
    }

    this.playerHand.unselectAll();

    this.bga.actions.performAction("actDiscardCard", {
      card_id: card_id,
    });
  }

  onPlayerSelected(evt) {
    console.log("onPlayerSelected");

    evt.preventDefault();
    evt.stopPropagation();

    if (!this.bga.actions.checkAction("actSelectTarget")) {
      return;
    }

    var target_id = evt.currentTarget.id
      .replace("player_", "")
      .replace("_area", "");

    this.bga.actions.performAction("actSelectTarget", {
      target_id: target_id,
    });
  }

  onPlayReaction(card_id) {
    console.log("onPlayReaction: " + card_id);

    if (!this.bga.actions.checkAction("actPlayReaction")) {
      return;
    }

    this.playerHand.unselectAll();

    this.bga.actions.performAction("actPlayReaction", {
      card_id: card_id,
    });
  }

  onPassReaction() {
    console.log("onPassReaction");

    if (!this.bga.actions.checkAction("actPassReaction")) {
      return;
    }

    this.bga.actions.performAction("actPassReaction");
  }

  onTakeTree() {
    console.log("onTakeTree");

    if (!this.bga.actions.checkAction("actChooseTakeTree")) {
      return;
    }

    this.bga.actions.performAction("actChooseTakeTree", { take_tree: true });
  }

  onDeclineTree() {
    console.log("onDeclineTree");

    if (!this.bga.actions.checkAction("actChooseTakeTree")) {
      return;
    }

    this.bga.actions.performAction("actChooseTakeTree", { take_tree: false });
  }

  onRollSave() {
    console.log("onRollSave");

    if (!this.bga.actions.checkAction("actRollSave")) {
      return;
    }

    this.bga.actions.performAction("actRollSave");
  }

  onChopRoll() {
    console.log("onChopRoll");

    if (!this.bga.actions.checkAction("actChopRoll")) {
      return;
    }

    this.bga.actions.performAction("actChopRoll");
  }

  onConfirmChopResult(evt) {
    console.log("onConfirmChopResult");
    if (evt) {
      evt.preventDefault();
      evt.stopPropagation();
    }

    if (!this.bga.actions.checkAction("actConfirmChopResult")) {
      return;
    }

    if (this.chopResultDialog) {
      this.chopResultDialog.destroy();
      this.chopResultDialog = null;
    }

    this.bga.actions.performAction("actConfirmChopResult");
  }

  ///////////////////////////////////////////////////
  //// Reaction to cometD notifications

  setupNotifications() {
    console.log("notifications subscriptions setup");

    this.bga.notifications.setupPromiseNotifications({
      prefix: "notif_",
      handlers: [this],
    });
  }

  notif_cardPlayed(args) {
    console.log("notif_cardPlayed", args);

    if (
      args.player_id == this.bga.players.getActivePlayerId() &&
      this.playerHand
    ) {
      this.playerHand.removeFromStockById(args.card_id);
    }
  }

  notif_cardDiscarded(args) {
    console.log("notif_cardDiscarded", args);

    if (
      args.player_id == this.bga.players.getActivePlayerId() &&
      this.playerHand
    ) {
      this.playerHand.removeFromStockById(args.card_id);
    }
  }

  notif_cardDrawn(args) {
    console.log("notif_cardDrawn", args);

    if (this.playerHand) {
      this.addCardToHand(args.card);
    }
  }

  notif_cardReturned(args) {
    console.log("notif_cardReturned", args);

    if (
      args.player_id == this.bga.players.getCurrentPlayerId() &&
      this.playerHand
    ) {
      this.addCardToHand({
        id: args.card_id,
        type_arg: args.card_type_arg,
      });
    }

    this.bga.dialogs.showMessage(
      args.card_name + _(" has no valid targets and was returned to hand."),
      "info",
    );
  }

  notif_targetSelected(args) {
    console.log("notif_targetSelected", args);

    var targetArea = $("player_" + args.target_id + "_area");
    if (targetArea) {
      targetArea.classList.add("targeted");
      setTimeout(function () {
        targetArea.classList.remove("targeted");
      }, 2000);
    }
  }

  notif_reactionPlayed(args) {
    console.log("notif_reactionPlayed", args);

    if (
      args.player_id == this.bga.players.getCurrentPlayerId() &&
      this.playerHand
    ) {
      this.playerHand.removeFromStockById(args.card_id);
    }
  }

  notif_reactionPassed(args) {
    console.log("notif_reactionPassed", args);
  }

  notif_treeDrawn(args) {
    console.log("notif_treeDrawn", args);

    var tree = {
      id: args.tree_id,
      card_id: args.card_id,
      card_type_arg: args.card_type_arg,
      type: args.tree_type,
      chops_required: args.chops_required,
      chops_current: 0,
      points: args.points,
    };

    this.displayTree(args.player_id, tree);
  }

  notif_treeChopped(args) {
    console.log("notif_treeChopped", args);

    this.updateChopProgress(args.player_id, args.chops_current);
  }

  notif_treeCompleted(args) {
    console.log("notif_treeCompleted", args);

    var treeArea = $("tree_area_" + args.player_id);
    if (treeArea) {
      treeArea.innerHTML = "";
    }

    if (args.card_type_arg) {
      this.displayCutTree(args.player_id, {
        card_type_arg: args.card_type_arg,
        points_value: args.points,
        cut_tree_id: args.tree_id,
      });
    }

    if (this["scoreCounter_" + args.player_id]) {
      this["scoreCounter_" + args.player_id].toValue(args.new_score);
    }
  }

  notif_scoreUpdate(args) {
    console.log("notif_scoreUpdate", args);

    if (this["scoreCounter_" + args.player_id]) {
      this["scoreCounter_" + args.player_id].toValue(args.score);
    }
  }

  notif_equipmentPlaced(args) {
    console.log("notif_equipmentPlaced", args);
    this.displayEquipment(args.player_id, {
      card_id: args.card_id,
      card_type_arg: args.card_type_arg,
      equipment_type: args.equipment_type,
    });
  }

  notif_equipmentDiscarded(args) {
    console.log("notif_equipmentDiscarded", args);
    this.removePlayAreaCard(args.card_id);
  }

  notif_modifierApplied(args) {
    console.log("notif_modifierApplied", args);
    this.displayModifier(args.player_id, {
      card_id: args.card_id,
      card_type_arg: args.card_type_arg,
      modifier_value: args.modifier_value,
    });
  }

  notif_modifierPrevented(args) {
    console.log("notif_modifierPrevented", args);
  }

  notif_helpPlaced(args) {
    console.log("notif_helpPlaced", args);
    if (args.replaced_card_id) {
      this.removePlayAreaCard(args.replaced_card_id);
    }
    this.displayHelp(args.player_id, {
      card_id: args.card_id,
      card_type_arg: args.card_type_arg,
      help_type: args.help_type,
    });
  }

  notif_helpDiscarded(args) {
    console.log("notif_helpDiscarded", args);
    this.removePlayAreaCard(args.card_id);
  }

  notif_helpStolen(args) {
    console.log("notif_helpStolen", args);
    this.removePlayAreaCard(args.card_id);
    if (args.replaced_card_id) {
      this.removePlayAreaCard(args.replaced_card_id);
    }
    this.displayHelp(args.player_id, {
      card_id: args.card_id,
      card_type_arg: args.card_type_arg,
      help_type: args.help_type,
    });
  }

  notif_equipmentStolen(args) {
    console.log("notif_equipmentStolen", args);
    this.removePlayAreaCard(args.card_id);
    if (args.replaced_card_id) {
      this.removePlayAreaCard(args.replaced_card_id);
    }
    this.displayEquipment(args.player_id, {
      card_id: args.card_id,
      card_type_arg: args.card_type_arg,
      equipment_type: args.equipment_type,
    });
  }

  notif_axeBreakFailed(args) {
    console.log("notif_axeBreakFailed", args);
  }

  notif_forestFire(args) {
    console.log("notif_forestFire", args);
    var players = args.affected_players || [];
    for (var i = 0; i < players.length; i++) {
      var treeArea = $("tree_area_" + players[i]);
      if (treeArea) {
        treeArea.innerHTML = "";
      }
    }
  }

  notif_switchTags(args) {
    console.log("notif_switchTags", args);

    if (args.player_tree) {
      var el = $("cut_tree_" + args.player_tree.cut_tree_id);
      if (el) el.remove();
    }
    if (args.target_tree) {
      var el = $("cut_tree_" + args.target_tree.cut_tree_id);
      if (el) el.remove();
    }

    if (args.player_tree) {
      this.displayCutTree(args.target_id, {
        card_type_arg: args.player_tree.card_type_arg,
        points_value: args.player_tree.points_value,
        cut_tree_id: args.player_tree.cut_tree_id,
      });
    }

    if (args.target_tree) {
      this.displayCutTree(args.player_id, {
        card_type_arg: args.target_tree.card_type_arg,
        points_value: args.target_tree.points_value,
        cut_tree_id: args.target_tree.cut_tree_id,
      });
    }

    if (this["scoreCounter_" + args.player_id]) {
      this["scoreCounter_" + args.player_id].toValue(args.player_new_score);
    }
    if (this["scoreCounter_" + args.target_id]) {
      this["scoreCounter_" + args.target_id].toValue(args.target_new_score);
    }
  }

  notif_treeHugger(args) {
    console.log("notif_treeHugger", args);
    this.setSkipTurnIndicator(args.player_id, true);
  }

  notif_sasquatchMating(args) {
    console.log("notif_sasquatchMating", args);
    this.setSkipTurnIndicator(args.player_id, true);
  }

  notif_treeTaken(args) {
    console.log("notif_treeTaken", args);

    var targetTreeArea = $("tree_area_" + args.player_id);
    if (targetTreeArea) {
      targetTreeArea.innerHTML = "";
    }

    if (args.tree) {
      var tree = {
        id: args.tree.tree_id,
        card_id: args.tree.card_id,
        card_type_arg: args.tree.card_type_arg,
        type: args.tree.tree_type,
        chops_required: args.tree.chops_required,
        chops_current: args.tree.chop_count,
        points: args.tree.points_value,
      };
      this.displayTree(args.active_id, tree);
    }
  }

  notif_treeTakeDeclined(args) {
    console.log("notif_treeTakeDeclined", args);
  }

  notif_sasquatchSightingRoll(args) {
    console.log("notif_sasquatchSightingRoll", args);
    if (args.loses_turn) {
      this.setSkipTurnIndicator(args.player_id, true);
    }
  }

  notif_choppingRoll(args) {
    console.log("notif_choppingRoll", args);
    this.updateChopProgress(args.player_id, args.new_chop_count);
  }

  notif_choppingSkipped(args) {
    console.log("notif_choppingSkipped", args);
  }

  notif_apprenticeRoll(args) {
    console.log("notif_apprenticeRoll", args);
    if (args.is_chop) {
      this.updateChopProgress(args.player_id, args.new_chop_count);
    }
  }

  notif_modifierRemoved(args) {
    console.log("notif_modifierRemoved", args);
    this.removePlayAreaCard(args.card_id);
  }

  notif_playerSkipped(args) {
    console.log("notif_playerSkipped", args);
    this.setSkipTurnIndicator(args.player_id, false);
  }

  notif_cardBlocked(args) {
    console.log("notif_cardBlocked", args);
  }
}
