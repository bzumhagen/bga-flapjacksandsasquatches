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

const [Counter] = await importDojoLibs(["ebg/counter"]);
const BgaAnimations = await importEsmLib("bga-animations", "1.x");
const BgaCards = await importEsmLib("bga-cards", "1.x");
const BgaDice = await importEsmLib("bga-dice", "1.x");

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
    this.cutPileStocks = {};

    // Track current game state
    this.pendingTarget = null;

    // Animation manager
    this.animationManager = new BgaAnimations.Manager({
      animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
    });

    // Dice manager
    this.diceManager = new BgaDice.Manager({
      animationManager: this.animationManager,
      type: "flapjacks-die",
    });
  }

  setup(gamedatas) {
    console.log("Starting game setup");
    console.log(gamedatas);

    // Store game data
    this.gamedatas = gamedatas;

    // Create card managers (needs gamedatas for sprite lookups)
    this.jackCardManager = new BgaCards.Manager({
      animationManager: this.animationManager,
      type: "fj-jack-card",
      cardWidth: this.cardWidth,
      cardHeight: this.cardHeight,
      getId: (card) => card.id,
      isCardVisible: (card) => !!card.card_type_arg,
      setupDiv: (card, div) => {
        div.classList.add("fj-jack-card");
      },
      setupFrontDiv: (card, div) => {
        var cardDef = this.gamedatas.jackCards[card.card_type_arg];
        if (!cardDef) return;
        var pos = cardDef.sprite_position || 0;
        var col = pos % 9;
        var row = Math.floor(pos / 9);
        div.style.backgroundImage =
          "url(" + g_gamethemeurl + "img/red_cards.jpg)";
        div.style.backgroundPosition =
          (col * 100) / 8 + "% " + (row * 100) / 4 + "%";
        div.style.backgroundSize = 9 * this.cardWidth + "px auto";
      },
      setupBackDiv: (card, div) => {
        div.style.backgroundImage =
          "url(" + g_gamethemeurl + "img/red_cards.jpg)";
        div.style.backgroundPosition = "100% 0%";
        div.style.backgroundSize = 9 * this.cardWidth + "px auto";
      },
    });

    this.treeCardManager = new BgaCards.Manager({
      animationManager: this.animationManager,
      type: "fj-tree-card",
      cardWidth: this.cardWidth,
      cardHeight: this.cardHeight,
      getId: (card) => card.id,
      isCardVisible: (card) => !!card.card_type_arg,
      setupDiv: (card, div) => {
        div.classList.add("fj-tree-card");
      },
      setupFrontDiv: (card, div) => {
        var treeDef = this.gamedatas.treeCards[card.card_type_arg];
        if (!treeDef) return;
        var pos = treeDef.sprite_position || 0;
        var col = pos % 4;
        var row = Math.floor(pos / 4);
        div.style.backgroundImage =
          "url(" + g_gamethemeurl + "img/tree_cards.jpg)";
        div.style.backgroundPosition = (col * 100) / 3 + "% " + row * 100 + "%";
        div.style.backgroundSize = 4 * this.cardWidth + "px auto";
      },
      setupBackDiv: (card, div) => {
        div.style.backgroundImage =
          "url(" + g_gamethemeurl + "img/tree_cards.jpg)";
        div.style.backgroundPosition = "100% 100%";
        div.style.backgroundSize = 4 * this.cardWidth + "px auto";
      },
    });

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

    // Setup player boards and per-player stocks
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

      // Create per-player card stocks
      this.equipmentStocks[player_id] = new BgaCards.LineStock(
        this.jackCardManager,
        $("equipment_area_" + player_id),
      );
      this.helpStocks[player_id] = new BgaCards.LineStock(
        this.jackCardManager,
        $("help_area_" + player_id),
      );
      this.modifierStocks[player_id] = new BgaCards.LineStock(
        this.jackCardManager,
        $("modifier_area_" + player_id),
      );
      this.cutPileStocks[player_id] = new BgaCards.LineStock(
        this.treeCardManager,
        $("cut_pile_" + player_id),
      );
    }

    // Setup player hand
    this.playerHand = new BgaCards.HandStock(
      this.jackCardManager,
      $("myhand"),
      {
        sort: (a, b) => {
          var typeWeights = {
            equipment: 100,
            plus_minus: 200,
            help: 300,
            action: 400,
            sasquatch: 500,
            reaction: 600,
          };
          var defA = this.gamedatas.jackCards[a.card_type_arg];
          var defB = this.gamedatas.jackCards[b.card_type_arg];
          var wA =
            (defA ? typeWeights[defA.type] || 0 : 0) + Number(a.card_type_arg);
          var wB =
            (defB ? typeWeights[defB.type] || 0 : 0) + Number(b.card_type_arg);
          return wA - wB;
        },
      },
    );
    this.playerHand.onSelectionChange = () => {
      this.onPlayerHandSelectionChanged();
    };

    // Setup deck components
    this.jackDeck = new BgaCards.Deck(this.jackCardManager, $("jack_deck"), {
      cardNumber: gamedatas.jackDeckCount || 0,
    });
    this.treeDeck = new BgaCards.Deck(this.treeCardManager, $("tree_deck"), {
      cardNumber: gamedatas.treeDeckCount || 0,
    });
    this.discardPile = new BgaCards.Deck(
      this.jackCardManager,
      $("discard_pile"),
      { cardNumber: gamedatas.discardCount || 0 },
    );

    // Show top discard card if available
    if (gamedatas.topDiscard) {
      this.discardPile.addCard({
        id: gamedatas.topDiscard.id,
        card_type_arg: gamedatas.topDiscard.type_arg,
      });
    }

    // Display initial game state
    this.updateDisplay(gamedatas);

    // Setup game notifications
    this.setupNotifications();

    console.log("Ending game setup");
  }

  updateDisplay(gamedatas) {
    if (gamedatas.hand) {
      for (var i in gamedatas.hand) {
        var card = gamedatas.hand[i];
        this.playerHand.addCard({
          id: card.id,
          card_type_arg: card.type_arg,
        });
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
        var eq = gamedatas.equipment[i];
        this.equipmentStocks[eq.player_id].addCard({
          id: eq.card_id,
          card_type_arg: eq.card_type_arg,
        });
      }
    }

    if (gamedatas.helpCards) {
      for (var i in gamedatas.helpCards) {
        var h = gamedatas.helpCards[i];
        this.helpStocks[h.player_id].addCard({
          id: h.card_id,
          card_type_arg: h.card_type_arg,
        });
      }
    }

    if (gamedatas.modifiers) {
      for (var i in gamedatas.modifiers) {
        var m = gamedatas.modifiers[i];
        this.modifierStocks[m.player_id].addCard({
          id: m.card_id,
          card_type_arg: m.card_type_arg,
        });
      }
    }

    if (gamedatas.cutTrees) {
      for (var i in gamedatas.cutTrees) {
        var ct = gamedatas.cutTrees[i];
        this.cutPileStocks[ct.player_id].addCard({
          id: ct.cut_tree_id,
          card_type_arg: ct.card_type_arg,
        });
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
      this.playerHand.setSelectionMode("single");
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
      var el = document.getElementById(
        this.treeCardManager.getId({ id: tree.cut_tree_id }) + "",
      );
      if (!el) {
        // Try the bga-cards generated element
        el = $("cut_pile_" + this.bga.players.getCurrentPlayerId());
        if (el)
          el = el.querySelector('[data-card-id="' + tree.cut_tree_id + '"]');
      }
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
      var el = document.getElementById(
        this.treeCardManager.getId({ id: tree.cut_tree_id }) + "",
      );
      if (!el) {
        el = document.querySelector(
          '[data-card-id="' + tree.cut_tree_id + '"]',
        );
      }
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
      var prevEl = document.querySelector('[data-card-id="' + prevId + '"]');
      if (prevEl) prevEl.classList.remove("selected");
    }

    if (side === "my") {
      this.switchTagsSelection.myTreeId = cutTreeId;
    } else {
      this.switchTagsSelection.targetTreeId = cutTreeId;
    }

    var el = document.querySelector('[data-card-id="' + cutTreeId + '"]');
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
      this.playerHand.setSelectionMode("single");
    }
  }

  async onEnteringChoppingRollResult(args) {
    if (!this.bga.players.isCurrentPlayerActive()) return;

    // Remove any previous roll area
    var existing = $("chop_roll_area");
    if (existing) existing.remove();

    var html = '<div id="chop_roll_area" class="chop_roll_results">';
    html += "<h3>" + _("Chopping Roll") + "</h3>";
    html += '<div id="chop_dice_stock" class="dice_row"></div>';

    if (args.apprentice_die !== null && args.apprentice_die !== undefined) {
      html += '<div id="apprentice_dice_stock" class="apprentice_row"></div>';
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

    html += "</div>";

    // Insert inside center area (to the right of the decks)
    var centerArea = $("center_area");
    if (centerArea) {
      centerArea.insertAdjacentHTML("beforeend", html);
    }

    // Create dice stocks, add dice, then roll
    var diceResults = args.dice_results || [];
    if (diceResults.length > 0) {
      var diceData = diceResults.map((face, i) => ({ id: i + 1, face: face }));
      this.chopDiceStock = new BgaDice.LineStock(
        this.diceManager,
        document.getElementById("chop_dice_stock"),
      );
      await this.chopDiceStock.addDice(diceData);
      await this.chopDiceStock.rollDice(diceData);
    }

    if (args.apprentice_die !== null && args.apprentice_die !== undefined) {
      var apprenticeData = [{ id: 100, face: args.apprentice_die }];
      this.apprenticeDiceStock = new BgaDice.LineStock(
        this.diceManager,
        document.getElementById("apprentice_dice_stock"),
      );
      await this.apprenticeDiceStock.addDice(apprenticeData);
      await this.apprenticeDiceStock.rollDice(apprenticeData);

      var apprenticeText = args.apprentice_chop ? _("Chop!") : _("No chop");
      var label = document.createElement("span");
      label.className = "apprentice_label";
      label.textContent = _("Apprentice") + ": " + apprenticeText;
      document.getElementById("apprentice_dice_stock").appendChild(label);
    }
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
          this.playerHand.setSelectionMode("none");
        }
        break;

      case "choppingRollResult":
        var rollArea = $("chop_roll_area");
        if (rollArea) rollArea.remove();
        this.chopDiceStock = null;
        this.apprenticeDiceStock = null;
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
    document.querySelectorAll(".selectable").forEach(function (el) {
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
          this.bga.statusBar.addActionButton(
            _("Continue"),
            () => {
              this.onConfirmChopResult();
            },
            { id: "button_confirm_chop" },
          );
          break;
      }
    }
  }

  ///////////////////////////////////////////////////
  //// Utility methods

  onPlayerHandSelectionChanged() {
    var items = this.playerHand.getSelection();

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

    var rollArea = $("chop_roll_area");
    if (rollArea) rollArea.remove();
    this.chopDiceStock = null;
    this.apprenticeDiceStock = null;

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

  async notif_cardPlayed(args) {
    console.log("notif_cardPlayed", args);

    // Equipment, help, and plus_minus cards are moved from hand by their
    // placement handler (equipmentPlaced, helpPlaced, modifierApplied)
    // using fromStock for a smooth animation. Action and sasquatch cards
    // don't place anywhere, so animate them from hand to discard pile.
    var placementTypes = ["equipment", "help", "plus_minus"];
    if (
      placementTypes.indexOf(args.card_type) === -1 &&
      args.player_id == this.bga.players.getCurrentPlayerId() &&
      this.playerHand
    ) {
      var card = { id: args.card_id, card_type_arg: args.card_type_arg };
      await this.discardPile.addCard(card, {
        fromStock: this.playerHand,
      });
    }
  }

  async notif_cardDiscarded(args) {
    console.log("notif_cardDiscarded", args);

    if (
      args.player_id == this.bga.players.getCurrentPlayerId() &&
      this.playerHand
    ) {
      var card = { id: args.card_id, card_type_arg: args.card_type_arg };
      await this.discardPile.addCard(card, {
        fromStock: this.playerHand,
      });
    }
  }

  async notif_cardDrawn(args) {
    console.log("notif_cardDrawn", args);

    if (this.playerHand) {
      var card = { id: args.card.id, card_type_arg: args.card.type_arg };
      await this.playerHand.addCard(card, { fromStock: this.jackDeck });
    }
  }

  async notif_cardReturned(args) {
    console.log("notif_cardReturned", args);

    if (
      args.player_id == this.bga.players.getCurrentPlayerId() &&
      this.playerHand
    ) {
      await this.playerHand.addCard({
        id: args.card_id,
        card_type_arg: args.card_type_arg,
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

  async notif_reactionPlayed(args) {
    console.log("notif_reactionPlayed", args);

    if (
      args.player_id == this.bga.players.getCurrentPlayerId() &&
      this.playerHand
    ) {
      this.playerHand.removeCard({ id: args.card_id });
    }
  }

  notif_reactionPassed(args) {
    console.log("notif_reactionPassed", args);
  }

  async notif_treeDrawn(args) {
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

    // Animate from tree deck to tree area
    var treeEl = $("tree_" + args.player_id);
    var treeDeck = $("tree_deck");
    if (treeEl && treeDeck) {
      await this.animationManager.slideAndAttach(
        treeEl,
        $("tree_area_" + args.player_id),
        { fromElement: treeDeck },
      );
    }
  }

  notif_treeChopped(args) {
    console.log("notif_treeChopped", args);

    this.updateChopProgress(args.player_id, args.chops_current);
  }

  async notif_treeCompleted(args) {
    console.log("notif_treeCompleted", args);

    var treeArea = $("tree_area_" + args.player_id);

    if (treeArea) {
      treeArea.innerHTML = "";
    }

    if (args.card_type_arg) {
      var card = {
        id: args.tree_id,
        card_type_arg: args.card_type_arg,
      };
      await this.cutPileStocks[args.player_id].addCard(card, {
        fromElement: treeArea,
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

  async notif_equipmentPlaced(args) {
    console.log("notif_equipmentPlaced", args);
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    await this.equipmentStocks[args.player_id].addCard(card, {
      fromStock: this.playerHand,
    });
  }

  async notif_equipmentDiscarded(args) {
    console.log("notif_equipmentDiscarded", args);
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    var stock = this.equipmentStocks[args.player_id];
    await this.discardPile.addCard(card, { fromStock: stock });
  }

  async notif_modifierApplied(args) {
    console.log("notif_modifierApplied", args);
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    await this.modifierStocks[args.player_id].addCard(card, {
      fromStock: this.playerHand,
    });
  }

  notif_modifierPrevented(args) {
    console.log("notif_modifierPrevented", args);
  }

  async notif_helpPlaced(args) {
    console.log("notif_helpPlaced", args);
    if (args.replaced_card_id) {
      this.helpStocks[args.player_id].removeCard({ id: args.replaced_card_id });
    }
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    await this.helpStocks[args.player_id].addCard(card, {
      fromStock: this.playerHand,
    });
  }

  async notif_helpDiscarded(args) {
    console.log("notif_helpDiscarded", args);
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    var stock = this.helpStocks[args.player_id];
    await this.discardPile.addCard(card, { fromStock: stock });
  }

  async notif_helpStolen(args) {
    console.log("notif_helpStolen", args);
    var fromStock = this.helpStocks[args.target_id];
    if (args.replaced_card_id) {
      this.helpStocks[args.player_id].removeCard({ id: args.replaced_card_id });
    }
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    await this.helpStocks[args.player_id].addCard(card, { fromStock });
  }

  async notif_equipmentStolen(args) {
    console.log("notif_equipmentStolen", args);
    var fromStock = this.equipmentStocks[args.target_id];
    if (args.replaced_card_id) {
      this.equipmentStocks[args.player_id].removeCard({
        id: args.replaced_card_id,
      });
    }
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    await this.equipmentStocks[args.player_id].addCard(card, { fromStock });
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

  async notif_switchTags(args) {
    console.log("notif_switchTags", args);

    if (args.player_tree) {
      this.cutPileStocks[args.player_id].removeCard({
        id: args.player_tree.cut_tree_id,
      });
    }
    if (args.target_tree) {
      this.cutPileStocks[args.target_id].removeCard({
        id: args.target_tree.cut_tree_id,
      });
    }

    if (args.player_tree) {
      await this.cutPileStocks[args.target_id].addCard({
        id: args.player_tree.cut_tree_id,
        card_type_arg: args.player_tree.card_type_arg,
      });
    }

    if (args.target_tree) {
      await this.cutPileStocks[args.player_id].addCard({
        id: args.target_tree.cut_tree_id,
        card_type_arg: args.target_tree.card_type_arg,
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

  async notif_treeTaken(args) {
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

      var treeEl = $("tree_" + args.active_id);
      if (treeEl && targetTreeArea) {
        await this.animationManager.slideAndAttach(
          treeEl,
          $("tree_area_" + args.active_id),
          { fromElement: targetTreeArea },
        );
      }
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

  async notif_modifierRemoved(args) {
    console.log("notif_modifierRemoved", args);
    var card = { id: args.card_id, card_type_arg: args.card_type_arg };
    var stock = this.modifierStocks[args.player_id];
    await this.discardPile.addCard(card, { fromStock: stock });
  }

  notif_playerSkipped(args) {
    console.log("notif_playerSkipped", args);
    this.setSkipTurnIndicator(args.player_id, false);
  }

  notif_cardBlocked(args) {
    console.log("notif_cardBlocked", args);
  }
}
