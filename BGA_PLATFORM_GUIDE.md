# Board Game Arena (BGA) Platform Developer Guide

A comprehensive reference for key features, restrictions, and best practices when developing games on the BGA platform.

---

## 📋 Table of Contents

1. [Technology Stack](#technology-stack)
2. [File Structure](#file-structure)
3. [Database Schema](#database-schema)
4. [PHP Game Logic](#php-game-logic)
5. [JavaScript Client Interface](#javascript-client-interface)
6. [State Machine](#state-machine)
7. [Framework Components](#framework-components)
8. [Common Patterns](#common-patterns)
9. [Critical Restrictions](#critical-restrictions)
10. [Best Practices](#best-practices)
11. [Troubleshooting](#troubleshooting)

---

## 🔧 Technology Stack

### Current Versions
- **PHP:** 8.4
- **MySQL:** 5.7
- **JavaScript Framework:** Dojo Toolkit 1.15
- **Icons:** Font Awesome 4.7 and 6.4.0

### Development Requirements
- Software development skills required
- Access to BGA Studio platform
- Recommended: Complete "Tutorial reversi" before starting

---

## 📁 File Structure

### Required Files (Modern BGA)

| File | Purpose | Notes |
|------|---------|-------|
| `dbmodel.sql` | Database schema definition | |
| `gameinfos.inc.php` | Game metadata (name, players, basic config) | Minimal - most metadata now in Game Metadata Manager |
| `material.inc.php` | Static game data (card types, constants) | |
| `[gamename].game.php` | Main server-side game logic | Use auto-wired actions |
| `[gamename].js` | Client-side interface logic | Use `bgaPerformAction()` |
| `[gamename].view.php` | Dynamic HTML generation | |
| `[gamename]_[gamename].tpl` | Static HTML templates | |
| `[gamename].css` | Game styling | |
| `states.inc.php` | Game state machine definition | No state 1 or 99 needed |
| `stats.json` | Statistics tracking configuration | Replaces stats.inc.php |
| `gameoptions.json` | Game options/variants | Replaces gameoptions.inc.php |
| `gamepreferences.json` | Player preferences | Optional |
| `modules/php/Game.php` | Module placeholder | Required but can be empty |

### Deprecated Files (Do Not Use)
- ❌ `[gamename].action.php` - Use auto-wired actions instead
- ❌ `stats.inc.php` - Use stats.json
- ❌ `gameoptions.inc.php` - Use gameoptions.json
- ❌ `version.php` - Obsolete
- ❌ `img/game_box.jpg` - Use Game Metadata Manager
- ❌ `img/game_box.png` - Use Game Metadata Manager

### Optional Directories
- `States/` - State machine class implementations
- `img/` - Visual assets (sprites, backgrounds)
- `modules/` - Reusable code modules

---

## 📝 Game Configuration Files

### gameinfos.inc.php ⭐ Modern Minimal Format

Modern BGA has moved most game metadata to the **Game Metadata Manager** web interface. The gameinfos.inc.php file should now contain only essential configuration.

#### Deprecated Keys (Managed in Game Metadata Manager)
Remove these keys - they're now set via the web interface:
- ❌ `designer` - Set in Game Metadata Manager
- ❌ `artist` - Set in Game Metadata Manager  
- ❌ `year` - Set in Game Metadata Manager
- ❌ `presentation` - Set in Game Metadata Manager
- ❌ `complexity` - Set in Game Metadata Manager
- ❌ `luck` - Set in Game Metadata Manager
- ❌ `strategy` - Set in Game Metadata Manager
- ❌ `diplomacy` - Set in Game Metadata Manager
- ❌ `is_beta` - Set in Game Metadata Manager
- ❌ `tags` - Set in Game Metadata Manager
- ❌ `is_sandbox` - Obsolete
- ❌ `turnControl` - Obsolete

#### Modern gameinfos.inc.php Example
```php
<?php
$gameinfos = array(
    'game_name' => "My Amazing Game",
    'publisher' => 'My Publisher',
    'publisher_website' => 'https://publisher.com/',
    'publisher_bgg_id' => 1234,
    'bgg_id' => 5678,
    
    'players' => array(2, 3, 4),
    'suggest_player_number' => 4,
    'not_recommend_player_number' => null,
    
    'estimated_duration' => 30,
    'fast_additional_time' => 30,
    'medium_additional_time' => 40,
    'slow_additional_time' => 50,
    
    'tie_breaker_description' => "",
    'losers_not_ranked' => false,
    'solo_mode_ranked' => false,
    'is_coop' => 0,
    'language_dependency' => false,
    
    'player_colors' => array("ff0000", "008000", "0000ff", "ffa500"),
    'favorite_colors_support' => true,
    'game_interface_width' => array('min' => 740, 'max' => null)
);
```

### stats.json (Replaces stats.inc.php)

```json
{
  "table": {
    "rounds_played": {
      "id": 10,
      "name": "Number of rounds",
      "type": "int"
    }
  },
  "player": {
    "cards_played": {
      "id": 10,
      "name": "Cards played",
      "type": "int"
    },
    "points_scored": {
      "id": 11,
      "name": "Points scored",
      "type": "int"
    }
  }
}
```

**After creating stats.json:**
1. Click "Reload statistics configuration" in BGA Studio
2. Delete the old `stats.inc.php` file

### gameoptions.json (Replaces gameoptions.inc.php)

```json
[
  {
    "name": "Game Length",
    "values": {
      "1": {
        "name": "Short",
        "description": "Play to 50 points"
      },
      "2": {
        "name": "Normal",
        "description": "Play to 100 points",
        "tmdisplay": "Normal length"
      },
      "3": {
        "name": "Long",
        "description": "Play to 150 points"
      }
    },
    "default": 2
  }
]
```

**After creating gameoptions.json:**
1. Click "Reload game options configuration" in BGA Studio
2. Delete the old `gameoptions.inc.php` file

### gamepreferences.json (Optional)

Player-specific preferences (e.g., animations on/off, card back style).

```json
[
  {
    "name": 100,
    "needReload": false,
    "values": {
      "1": {"name": "Enabled"},
      "2": {"name": "Disabled"}
    },
    "default": 1
  }
]
```

---

## 💾 Database Schema

### Standard Tables (Auto-Created, DO NOT MODIFY)
- **global** - Framework globals
- **stats** - Game statistics
- **gamelog** - Game event log
- **player** - Player information (extendable)

### Player Table Default Columns
```sql
player_id           -- Unique player identifier
player_no           -- Player number (1, 2, 3...)
player_name         -- Display name
player_score        -- Primary score
player_score_aux    -- Tie-breaker score
player_table_order  -- Only during initialization
player_color        -- Assigned color
player_canal        -- Communication channel
player_avatar       -- Avatar URL
```

### Best Practices

✅ **DO:**
- Use `IF NOT EXISTS` in CREATE statements
- Use `ENGINE=InnoDB DEFAULT CHARSET=utf8`
- Define explicit PRIMARY KEYs
- Store dynamic game data in database
- Keep schemas simple (50-500 pieces max)
- Use custom database modules with type-checking

❌ **DON'T:**
- Modify global, stats, or gamelog tables
- Use TRUNCATE or DROP (causes implicit commits)
- Name columns identically to table names
- Store static data (use `material.inc.php` instead)
- Store translatable strings (use integer IDs)
- Add inline SQL comments (disables code)

### Extending Player Table
```sql
ALTER TABLE `player` ADD `player_custom_field` INT UNSIGNED NOT NULL DEFAULT '0';
```

### Typical Card Table
```sql
CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

### Schema Changes After Release
Use `upgradeTableDb()` method in Game.php:
```php
function upgradeTableDb($from_version) {
    if ($from_version <= 1404301345) {
        $sql = "ALTER TABLE DBPREFIX_card ADD ...";
        self::applyDbUpgradeToAllDB($sql);
    }
}
```

---

## 🎮 PHP Game Logic (Game.php)

### Required Methods

#### Constructor
```php
function __construct() {
    parent::__construct();
    self::initGameStateLabels([
        "my_variable" => 10,
        "my_option" => 100,
    ]);
}
```

#### Setup New Game ⭐ Modern Format
```php
protected function setupNewGame($players, $options = array()) {
    // Set player colors
    $gameinfos = self::getGameinfos();
    $default_colors = $gameinfos['player_colors'];
    
    // Create players
    $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
    $values = array();
    foreach($players as $player_id => $player) {
        $color = array_shift($default_colors);
        $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes($player['player_name'])."','".addslashes($player['player_avatar'])."')";
    }
    $sql .= implode($values, ',');
    self::DbQuery($sql);
    self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
    self::reloadPlayersBasicInfos();
    
    // Initialize game state values
    self::setGameStateInitialValue('round_number', 1);
    
    // Initialize statistics
    self::initStat('table', 'rounds_played', 0);
    self::initStat('player', 'cards_played', 0);
    
    // Deal initial cards, set up game board, etc.
    // ...
    
    // Activate first player
    $this->activeNextPlayer();
    
    // MODERN: Return initial state ID (replaces deprecated state 1)
    return 10;  // Start at your first real game state
}
```

#### Get All Data (for page refresh)
```php
protected function getAllDatas() {
    $result = array();
    $current_player_id = self::getCurrentPlayerId();
    
    // Get player info
    $sql = "SELECT player_id id, player_score score FROM player";
    $result['players'] = self::getCollectionFromDb($sql);
    
    // Get visible game data
    // DO NOT return private information visible to current player only
    
    return $result;
}
```

#### Game Progression
```php
function getGameProgression() {
    // Return 0-100 representing game completion percentage
    return 0;
}
```

### Player Information Methods

```php
// Active player (whose turn it is)
$active_player = self::getActivePlayerId();
$active_name = self::getActivePlayerName();

// Current player (who sent request)
$current_player = self::getCurrentPlayerId(); // DON'T use in setupNewGame() or zombieTurn()
$current_name = self::getCurrentPlayerName();

// All players
$players = self::loadPlayersBasicInfos();

// Specific player
$name = self::getPlayerNameById($player_id);
$color = self::getPlayerColorById($player_id);
```

### Database Access Methods

```php
// Generic query
self::DbQuery("UPDATE player SET player_score = 5 WHERE player_id = $player_id");

// Get associative array (first column as key)
$players = self::getCollectionFromDb("SELECT player_id, player_name FROM player");

// Get single row
$player = self::getObjectFromDB("SELECT * FROM player WHERE player_id = $id");

// Get array of rows
$cards = self::getObjectListFromDB("SELECT * FROM card WHERE card_location = 'hand'");

// Get single value
$count = self::getUniqueValueFromDB("SELECT COUNT(*) FROM card WHERE card_location = 'deck'");

// Sanitize user input
$safe = self::escapeStringForDB($user_input);
```

### State Management

```php
// Transition to next state
$this->gamestate->nextState('transitionName');

// Activate next/previous player
$this->activeNextPlayer();
$this->activePrevPlayer();

// Multiple active players
$this->gamestate->setAllPlayersMultiactive();
$this->gamestate->setPlayersMultiactive([$player1, $player2], 'next');
$this->gamestate->setPlayerNonMultiactive($player_id, 'transition');

// Check valid action
self::checkAction('playCard'); // Throws exception if invalid
```

### Global Variables

**BGA Globals (flexible, JSON-serialized):**
```php
// Set any value type
$this->globals->set('current_round', 5);
$this->globals->set('player_data', ['id' => 123, 'name' => 'Alice']);

// Get with default
$round = $this->globals->get('current_round', 1);
```

**Game State Values (numeric only, IDs 10-99):**
```php
// In constructor
self::initGameStateLabels(['current_round' => 10]);

// Get/Set
$round = self::getGameStateValue('current_round');
self::setGameStateValue('current_round', 5);
self::setGameStateInitialValue('current_round', 1);
```

### Notification System

```php
// Notify all players
$this->notify->all('cardPlayed', clienttranslate('${player_name} plays ${card_name}'), [
    'player_name' => $player_name, // Color-coded in log
    'card_name' => $card_name,
    'card_id' => $card_id,
    'preserve' => ['card_id'] // Preserve during replay
]);

// Notify single player (private)
$this->notify->player($player_id, 'privateInfo', '', [
    'hand_count' => 5
]);

// No log message
$this->notify->all('updateDisplay', '', ['data' => $data]);
```

**Notification Size Limit:** 128KB total per action

### Statistics

```php
// In setupNewGame()
$this->playerStats->init('cards_played', 0);
$this->tableStats->init('rounds_played', 0);

// Update stats
$this->playerStats->inc('cards_played', $player_id);
$this->playerStats->set('cards_played', 10, $player_id);
$this->tableStats->inc('rounds_played');
```

### Randomization

```php
// ALWAYS use bga_rand() for dice rolls (prevents cheating)
$die_result = bga_rand(1, 6);

// PHP shuffle() for arrays (e.g., decks)
shuffle($deck);
```

### Player Actions (Auto-wired) ⭐ Modern Approach

**Auto-wired actions** eliminate the need for `.action.php` passthrough files. Use PHP 8 attributes to define action parameters directly on game logic methods.

#### Basic Auto-wired Action
```php
// In [gamename].game.php
#[ActionParam('card_id', AT_posint, true)]  // Parameter validation attribute
function playCard(int $card_id) {
    self::checkAction('playCard');
    $player_id = self::getActivePlayerId();
    
    // Validate and execute action
    // ...
    
    // Notify
    $this->notify->all('cardPlayed', clienttranslate('${player_name} plays card'), [
        'player_name' => self::getActivePlayerName(),
        'card_id' => $card_id
    ]);
    
    // Transition state
    $this->gamestate->nextState('cardPlayed');
}
```

#### Multiple Parameters
```php
#[ActionParam('card_id', AT_posint, true)]
#[ActionParam('target_id', AT_posint, true)]
function playCardOnTarget(int $card_id, int $target_id) {
    self::checkAction('playCardOnTarget');
    // ... implementation
}
```

#### No Parameters
```php
// No attribute needed if no parameters
function passAction() {
    self::checkAction('pass');
    // ... implementation
}
```

#### Parameter Types
- `AT_posint` - Positive integer
- `AT_int` - Any integer
- `AT_bool` - Boolean
- `AT_alphanum` - Alphanumeric string
- `AT_numberlist` - Comma-separated list of numbers
- `AT_email` - Email address
- `AT_url` - URL

#### JavaScript Client Call
```javascript
// Modern approach with bgaPerformAction()
this.bgaPerformAction('playCard', {
    card_id: cardId
});

// Multiple parameters
this.bgaPerformAction('playCardOnTarget', {
    card_id: cardId,
    target_id: targetId
});

// No parameters
this.bgaPerformAction('passAction');
```

#### Legacy .action.php (Deprecated)
```php
// OLD WAY - Don't use this anymore
class action_mygame extends APP_GameAction {
    public function playCard() {
        self::setAjaxMode();
        $card_id = self::getArg("card_id", AT_posint, true);
        $this->game->playCard($card_id);
        self::ajaxResponse();
    }
}
```

**Benefits of Auto-wired Actions:**
- ✅ Less boilerplate code
- ✅ Better type safety with PHP 8 type hints
- ✅ Automatic parameter validation
- ✅ No duplicate code between action file and game logic
- ✅ Cleaner project structure (one less file)

### Zombie Player Handling

```php
function zombieTurn($state, $active_player) {
    $statename = $state['name'];
    
    if ($state['type'] === "activeplayer") {
        switch ($statename) {
            case 'playerTurn':
                // Auto-pass or make random valid move
                $this->gamestate->nextState("zombiePass");
                break;
        }
        return;
    }
    
    if ($state['type'] === "multipleactiveplayer") {
        $this->gamestate->setPlayerNonMultiactive($active_player, '');
        return;
    }
    
    throw new feException("Zombie mode not supported at state: " . $statename);
}
```

---

## 🎨 JavaScript Client Interface ([gamename].js)

### Required Methods

```javascript
constructor: function() {
    // Initialize variables
    this.cardWidth = 72;
    this.cardHeight = 96;
}

setup: function(gamedatas) {
    // Initialize game interface
    // Set up player boards
    // Create stocks/zones
    // Setup notifications
}

onEnteringState: function(stateName, args) {
    // Handle UI changes when entering state
    switch(stateName) {
        case 'playerTurn':
            this.updatePossibleMoves(args.args);
            break;
    }
}

onLeavingState: function(stateName) {
    // Cleanup when leaving state
}

onUpdateActionButtons: function(stateName, args) {
    // Add action buttons to status bar
    if(this.isCurrentPlayerActive()) {
        this.statusBar.addActionButton('btnPlayCard', _('Play Card'), 
            () => this.onPlayCard(), {color: 'primary'});
    }
}

setupNotifications: function() {
    // Link notifications to handlers
    bgaSetupPromiseNotifications([
        ['cardPlayed', 1000],
        ['cardDrawn', 500]
    ], this.notif_handlers);
}
```

### DOM Manipulation (Dojo)

```javascript
// Get element by ID
var element = $('elementId');

// Style manipulation
dojo.style('elementId', 'display', 'block');
dojo.style('elementId', {
    width: '100px',
    height: '50px'
});

// Class manipulation
dojo.addClass('elementId', 'myClass');
dojo.removeClass('elementId', 'myClass');
dojo.toggleClass('elementId', 'myClass');

// Query multiple elements
dojo.query('.myClass').forEach(function(node) {
    // Do something with each element
});

// Place/move elements
dojo.place('<div>New content</div>', 'containerId');
dojo.place('elementId', 'newParentId');
```

### Player Actions

```javascript
onPlayCard: function(evt) {
    dojo.stopEvent(evt); // Prevent default browser action
    
    // Validate action is possible
    if(!this.checkAction('playCard')) return;
    
    // Send to server
    this.bgaPerformAction('actPlayCard', {
        card_id: cardId,
        target: targetId
    });
}
```

### Notifications

```javascript
notif_handlers = {
    cardPlayed: async (notif) => {
        // Animate card from hand to table
        await this.slideToObject('card_' + notif.args.card_id, 'playArea');
        // Update game state
        this.playerHand.removeFromStockById(notif.args.card_id);
    },
    
    cardDrawn: async (notif) => {
        // Add card to hand
        this.playerHand.addToStockWithId(notif.args.card_type, notif.args.card_id);
    }
};

// Control notification timing
setupNotifications: function() {
    // Synchronous (wait for animations)
    bgaSetupPromiseNotifications([
        ['cardPlayed', 1000], // 1 second duration
    ], this.notif_handlers);
    
    // Or set individual notifications synchronous
    this.notifqueue.setSynchronous('cardPlayed', 1000);
}
```

### Animations (BgaAnimations)

```javascript
// Slide to object
await this.slideToObject('elementId', 'targetId');

// Slide and destroy
await this.slideToObjectAndDestroy('elementId', 'targetId');

// Fade out and destroy
await this.fadeOutAndDestroy('elementId');

// Rotate
await this.rotateTo('elementId', 90); // degrees

// Chain animations
await Promise.all([
    this.slideToObject('card1', 'deck'),
    this.slideToObject('card2', 'deck')
]);
```

### UI Components

#### Action Buttons
```javascript
this.statusBar.addActionButton('btnId', _('Button Label'), () => this.onButtonClick(), {
    color: 'primary',    // or 'secondary', 'alert'
    disabled: false,
    confirm: 'Are you sure?'
});
```

#### Dialogs
```javascript
// Confirmation
this.confirmationDialog('Are you sure?', () => {
    // User clicked yes
    this.bgaPerformAction('actConfirm');
});

// Multiple choice
this.multipleChoiceDialog('Choose an option:', ['Option A', 'Option B'], (choice) => {
    // choice is index of selected option
});
```

#### Tooltips
```javascript
// Simple text
this.addTooltip('elementId', _('This is a tooltip'), '');

// HTML content
this.addTooltipHtml('elementId', '<b>Bold</b> tooltip');

// Apply to all elements with class
this.addTooltipToClass('cardClass', _('Card information'), '');
```

### Client State (Multi-Step Actions)

```javascript
// Simulate state without server call
onSelectTarget: function() {
    this.setClientState('client_selectTarget', {
        descriptionmyturn: _('${you} must select a target')
    });
}

// Return to server state
onCancelSelection: function() {
    this.restoreServerGameState();
}
```

---

## 🔄 State Machine (states.inc.php)

### State Types

| Type | Description | Use Case |
|------|-------------|----------|
| `manager` | Framework-controlled | Initial setup, game end |
| `activeplayer` | Single active player | Normal turn-based play |
| `multipleactiveplayer` | Multiple active players | Simultaneous actions |
| `game` | No active players | Automatic transitions, calculations |

### State Definition Structure ⭐ Modern Format

**Note:** States 1 (gameSetup) and 99 (gameEnd) are **deprecated**. Return the initial state ID from `setupNewGame()` instead.

```php
$machinestates = array(
    // State 1 is deprecated - don't use it
    // Instead, return initial state from setupNewGame(): return 10;
    
    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must play a card'),
        "descriptionmyturn" => clienttranslate('${you} must play a card'),
        "type" => "activeplayer",
        "possibleactions" => array("playCard", "pass"),
        "transitions" => array(
            "playCard" => 3,
            "pass" => 4
        )
    ),
    
    20 => array(
        "name" => "nextPlayer",
        "description" => "",
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => array(
            "nextPlayer" => 10,
            "endGame" => 99  // Can still transition to 99, just don't define it
        )
    ),
    
    30 => array(
        "name" => "multiplayerState",
        "description" => clienttranslate('Other players must choose'),
        "descriptionmyturn" => clienttranslate('${you} must choose'),
        "type" => "multipleactiveplayer",
        "possibleactions" => array("choose"),
        "transitions" => array("next" => 20)
    )
    
    // State 99 is deprecated - don't define it
    // Framework handles game end automatically when you call:
    // $this->gamestate->nextState("endGame");
);
```

### State Properties

| Property | Required For | Description |
|----------|--------------|-------------|
| `name` | All | State identifier (no spaces) |
| `type` | All | State type (see above) |
| `description` | activeplayer, multipleactiveplayer | Status bar message |
| `descriptionmyturn` | activeplayer, multipleactiveplayer | Message for active player |
| `action` | game, manager | PHP method called on entry (prefix: `st`) |
| `possibleactions` | activeplayer, multipleactiveplayer | Array of valid action names |
| `transitions` | All | Map of transition names → target state IDs |
| `args` | Optional | PHP method to get state arguments (prefix: `arg`) |
| `updateGameProgression` | Optional | Call getGameProgression() on entry |

### Action Methods (stStateName)

```php
// Called automatically when entering "game" or "manager" states
function stNextPlayer() {
    // Do automatic game logic
    $player_id = self::activeNextPlayer();
    
    // Transition to next state
    $this->gamestate->nextState('nextPlayer');
}
```

### Argument Methods (argStateName)

```php
// Called before entering state to provide client with data
function argPlayerTurn() {
    return array(
        'possibleMoves' => $this->getPossibleMoves(),
        'cardCount' => $this->getCardCount()
    );
}
```

### Standard Flow Patterns

**Single Player Turn Loop:**
```
playerTurn (activeplayer) 
  → playCard action 
  → nextPlayer (game, calls stNextPlayer) 
  → playerTurn
```

**Multiple Active Players:**
```
drawCards (game, activates multiple players)
  → chooseCard (multipleactiveplayer)
  → all players choose
  → resolveChoices (game)
```

---

## 🧩 Framework Components

### Deck (PHP - Card Management)

```php
// In constructor
$this->cards = $this->deckFactory->createDeck('card');

// In setupNewGame()
$this->cards->createCards([
    ['type' => 'spade', 'type_arg' => 1, 'nbr' => 1],
    ['type' => 'spade', 'type_arg' => 2, 'nbr' => 1],
    // ... for all cards
]);

$this->cards->shuffle('deck');
$this->cards->pickCard('deck', $player_id); // Deals to player

// Auto-reshuffle discard into deck
$this->cards->autoreshuffle = true;
$this->cards->autoreshuffle_custom = ['deck' => 'discard'];

// Query cards
$cards = $this->cards->getCardsInLocation('hand', $player_id);
$count = $this->cards->countCardInLocation('deck');
$card = $this->cards->getCard($card_id);

// Move cards
$this->cards->moveCard($card_id, 'discard');
$this->cards->moveCards([$id1, $id2], 'discard');
$this->cards->playCard($card_id); // Moves to 'discard'
```

### Stock (JavaScript - Card Display)

```javascript
// Create stock
this.playerHand = new ebg.stock();
this.playerHand.create(this, $('myhand'), cardWidth, cardHeight);

// Configure sprite
this.playerHand.image_items_per_row = 10;

// Add card types
this.playerHand.addItemType(1, 1, spriteUrl, 0); // type_id, weight, url, sprite_position

// Selection mode
this.playerHand.setSelectionMode(0); // 0=none, 1=single, 2=multiple

// Selection appearance
this.playerHand.setSelectionAppearance('border'); // or 'class'

// Overlap (save space)
this.playerHand.horizontal_overlap = 20; // pixels
this.playerHand.vertical_overlap = 20;

// Add/remove items
this.playerHand.addToStock(cardType); // Untracked
this.playerHand.addToStockWithId(cardType, cardId, 'fromElement'); // Tracked with ID
this.playerHand.removeFromStockById(cardId, 'toElement');

// Events
dojo.connect(this.playerHand, 'onChangeSelection', this, 'onCardSelectionChanged');

// Get selection
var selectedCards = this.playerHand.getSelectedItems();
```

### Counter (JavaScript - Score Display)

```javascript
// Create counter
this.scoreCounter = new ebg.counter();
this.scoreCounter.create('player_score_' + player_id);

// Set value
this.scoreCounter.setValue(10);

// Increment/decrement
this.scoreCounter.incValue(5);  // +5
this.scoreCounter.incValue(-3); // -3

// Animate to value
this.scoreCounter.toValue(20);
```

### Zone (JavaScript - Board Areas)

```javascript
// Create zone for placing items
this.myZone = new ebg.zone();
this.myZone.create(this, 'zoneContainer', itemWidth, itemHeight);

// Add item
this.myZone.placeInZone('elementId');

// Remove item
this.myZone.removeFromZone('elementId');

// Get all items
var items = this.myZone.getItems();
```

---

## 🎯 Common Patterns

### Moving Cards Between Locations

**Server (PHP):**
```php
function actPlayCard($card_id) {
    $player_id = self::getActivePlayerId();
    $this->cards->playCard($card_id); // Moves to 'discard'
    
    $this->notify->all('cardPlayed', clienttranslate('${player_name} plays a card'), [
        'player_name' => self::getActivePlayerName(),
        'card_id' => $card_id,
        'card_type' => $this->cards->getCard($card_id)['type']
    ]);
}
```

**Client (JavaScript):**
```javascript
notif_cardPlayed: async function(notif) {
    // Animate from hand to play area
    await this.slideToObject('card_' + notif.args.card_id, 'playArea');
    
    // Update stocks
    this.playerHand.removeFromStockById(notif.args.card_id);
    this.playArea.addToStockWithId(notif.args.card_type, notif.args.card_id);
}
```

### Rolling Dice

**Server (PHP) - ALWAYS do random here:**
```php
function actRollDice() {
    $die1 = bga_rand(1, 6);
    $die2 = bga_rand(1, 6);
    
    $this->notify->all('diceRolled', clienttranslate('${player_name} rolls ${die1} and ${die2}'), [
        'player_name' => self::getActivePlayerName(),
        'die1' => $die1,
        'die2' => $die2
    ]);
}
```

**Client (JavaScript) - Display only:**
```javascript
notif_diceRolled: async function(notif) {
    // Animate dice
    $('die1').innerHTML = notif.args.die1;
    $('die2').innerHTML = notif.args.die2;
    // Show roll animation
}
```

### Dynamic HTML Templates

**Template (.tpl file):**
```html
<div id="mytemplate" class="jstpl_card">
    <div class="card" style="background-position: -{X}px -{Y}px"></div>
</div>
```

**JavaScript:**
```javascript
var html = this.format_block('jstpl_card', {
    X: cardType * 72,
    Y: 0
});
dojo.place(html, 'container');
```

### Multi-Step Client-Side Actions

```javascript
onPlayCard: function(cardId) {
    this.selectedCard = cardId;
    
    // Enter client state
    this.setClientState('client_selectTarget', {
        descriptionmyturn: _('${you} must select a target for the card')
    });
}

onSelectTarget: function(targetId) {
    // Send both selections to server
    this.bgaPerformAction('actPlayCardOnTarget', {
        card_id: this.selectedCard,
        target_id: targetId
    });
    
    // Return to server state
    this.restoreServerGameState();
}

onCancelAction: function() {
    this.selectedCard = null;
    this.restoreServerGameState();
}
```

---

## ⚠️ Critical Restrictions

### ❌ NEVER DO THESE:

1. **Never roll dice in JavaScript** - Always use `bga_rand()` in PHP to prevent cheating

2. **Never use getCurrentPlayerId() in:**
   - `setupNewGame()` - No current player exists
   - `zombieTurn()` - No logged-in player context

3. **Never send HTML from PHP** - Use notifications with data, build HTML in JavaScript

4. **Never use TRUNCATE or DROP** - Causes implicit commits that break transaction rollback

5. **Never name database columns same as table name** - Breaks replay functionality

6. **Never extend player table for resources** - Use dedicated resource tables

7. **Never modify global, stats, or gamelog tables** - Framework-controlled

8. **Never change database schema mid-game** - Define all tables in dbmodel.sql upfront

9. **Never bypass transaction system** - Let framework handle commits/rollbacks

10. **Never make non-random player order in ranked games** - Only allowed in friendly mode

### ⚠️ Important Limitations

- **Notification size limit:** 128KB total per action
- **Cannot modify database schema after game starts** - Must use `upgradeTableDb()`
- **SQL comments disable code** - Don't use inline comments in dbmodel.sql
- **AUTO_INCREMENT is not strictly sequential** - Don't rely on consecutive IDs

---

## ✅ Best Practices

### Database Design

✅ Store only **dynamic** data in database (game state, positions)
✅ Store **static** data in `material.inc.php` (card properties, constants)
✅ Use **simple schemas** (50-500 pieces typical)
✅ Use **(token_key, token_location, token_state)** pattern for most games
✅ Use **integer IDs** not strings for game data
✅ Create **indexes** for frequently queried columns

### PHP Game Logic

✅ **Validate first:** Always call `checkAction()` at start of action methods
✅ **Minimize notifications:** Send only essential data
✅ **Use descriptive names:** Clear state and action names
✅ **Handle zombies:** Implement `zombieTurn()` for all states
✅ **Preserve replay:** Add `'preserve' => ['field']` to notification args
✅ **Initialize stats:** Set all statistics in `setupNewGame()`
✅ **Use globals wisely:** BGA globals for complex data, game state values for numbers

### JavaScript Interface

✅ **Separate concerns:** HTML in .tpl, logic in .js, styling in .css
✅ **Use templates:** `format_block()` instead of string concatenation
✅ **Animate smoothly:** Use BgaAnimations library, not legacy Dojo
✅ **Handle promises:** Use async/await for animations
✅ **Cache selectors:** Store frequently used element references
✅ **Validate actions:** Check `checkAction()` before server calls
✅ **Use client states:** Multi-step interactions before server submission

### State Machine

✅ **Mirror rulebook:** State flow should match game rules naturally
✅ **Use descriptive names:** Clear state names (no spaces)
✅ **Auto-transition:** Use "game" states for automatic progressions
✅ **Update progression:** Set `updateGameProgression: true` on milestone states
✅ **Provide arguments:** Use `args` methods to send data to client

### Performance

✅ **Optimize animations:** Limit concurrent animations
✅ **Use CSS classes:** Instead of inline styles
✅ **Lazy load:** Don't render invisible elements
✅ **Cache data:** Store frequently accessed values
✅ **Minimize DOM queries:** Cache element references
✅ **Limit shadows:** Use box-shadow on rectangles, avoid filter: drop-shadow() on many elements

### Code Organization

✅ **Comment complex logic:** Explain non-obvious code
✅ **Use consistent naming:** Follow BGA conventions (st*, arg*, act*, notif_*)
✅ **Modularize:** Break large functions into smaller methods
✅ **Handle errors gracefully:** Throw UserException for rule violations
✅ **Test thoroughly:** All states, all card types, edge cases

---

## 🔍 Troubleshooting

### Common JavaScript Issues

**Problem:** Elements not appearing after creation
- **Cause:** `attachToNewParent` destroys connectors
- **Solution:** Use `attachToNewParentNoDestroy`

**Problem:** Animations break with zoomed/transformed parents
- **Cause:** Standard animations fail with CSS transforms
- **Solution:** Use "animation on oversurface" technique - clone element, animate on overlay

**Problem:** Notification parameters disappear on reload
- **Cause:** Only args in message string are preserved
- **Solution:** Add `'preserve' => ['field_name']` to notification

**Problem:** `bgaFormatText()` doesn't substitute dotted keys like `${card.name}`
- **Solution:** Flatten notification args or use custom formatting

### Common PHP Issues

**Problem:** Transaction rollback doesn't work
- **Cause:** TRUNCATE/DROP causes implicit commit
- **Solution:** Use DELETE instead of TRUNCATE

**Problem:** "Not logged" error
- **Cause:** Using `getCurrentPlayerId()` in `setupNewGame()` or `zombieTurn()`
- **Solution:** Don't access current player in these contexts

**Problem:** Notification too large
- **Cause:** Sending too much data (>128KB)
- **Solution:** Send only essential data, use separate notifications

### Debugging Techniques

**Client-side:**
```javascript
// Log to browser console
console.log('Debug info:', variable);

// Override notification queue for error capture
// (prevents silent failures)
```

**Server-side:**
```php
// Use debug log
self::debug("Variable value: " . json_encode($variable));

// Throw exceptions for debugging
throw new BgaVisibleSystemException("Debug: " . print_r($data, true));
```

**State visibility:**
```css
/* Auto-generated class on #overall-content */
#myElement {
    display: none;
}
.gamestate_playerTurn #myElement {
    display: block;
}
```

### Performance Issues

**Problem:** Lag with many elements
- **Cause:** Too many shadow effects (especially filter: drop-shadow())
- **Solution:** Use box-shadow instead, or add user preference to disable

**Problem:** Slow animations
- **Cause:** Too many concurrent animations
- **Solution:** Chain animations with Promise.all() for necessary simultaneous effects only

---

## 📚 Additional Resources

### Official Documentation
- [BGA Studio Main Page](https://en.doc.boardgamearena.com/Studio)
- [Tutorial: Reversi](https://en.doc.boardgamearena.com/Tutorial_reversi)
- [Studio Cookbook](https://en.doc.boardgamearena.com/BGA_Studio_Cookbook)
- [Studio FAQ](https://en.doc.boardgamearena.com/Studio_FAQ)
- [Pre-release Checklist](https://en.doc.boardgamearena.com/Pre-release_checklist)

### Developer Community
- BGA Discord Server
- BGA Development Forum
- Bug Tracking System
- Developer Blogs

### Learning Path
1. Complete "Tutorial reversi" walkthrough
2. Review sample games in BGA Studio
3. Follow complete game implementation guide
4. Consult pre-release checklist before publishing

---

## 📋 Migration Checklist: Legacy to Modern BGA

If you have an older BGA project, use this checklist to modernize it:

- [ ] Remove deprecated `states 1 and 99` from states.inc.php
- [ ] Add `return 10;` (or your initial state ID) at end of setupNewGame()
- [ ] Convert `.action.php` passthrough methods to auto-wired actions with `#[ActionParam]` attributes
- [ ] Delete `.action.php` file after converting all actions
- [ ] Replace `this.ajaxcall()` with `this.bgaPerformAction()` in JavaScript
- [ ] Create `stats.json` from stats.inc.php and delete the .inc.php file
- [ ] Create `gameoptions.json` from gameoptions.inc.php and delete the .inc.php file
- [ ] Create `gamepreferences.json` if needed
- [ ] Create empty `modules/php/Game.php` file
- [ ] Remove deprecated keys from `gameinfos.inc.php`:
  - [ ] designer, artist, year
  - [ ] presentation, complexity, luck, strategy, diplomacy
  - [ ] is_beta, tags
  - [ ] is_sandbox, turnControl
- [ ] Delete `version.php` if it exists
- [ ] Delete `img/game_box.jpg` and `img/game_box.png`
- [ ] Remove deprecated `getGameName()` function from game.php and view.php
- [ ] Set game metadata in Game Metadata Manager web interface
- [ ] Click "Reload game informations" after gameinfos.inc.php changes
- [ ] Click "Reload statistics configuration" after creating stats.json
- [ ] Click "Reload game options configuration" after creating gameoptions.json

---

**Last Updated:** 2025-11-29
**BGA Platform Version:** PHP 8.4, MySQL 5.7, Dojo 1.15
**Guide Version:** 2.0 - Modernized for 2025 BGA Standards
