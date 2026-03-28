# Flapjacks and Sasquatches - Developer's Guide

A Board Game Arena implementation of the card game "Flapjacks and Sasquatches" (2-8 players). This guide maps out the architecture so you know where to look when contributing or debugging.

## Tech Stack

- **Server:** PHP 8.4 on the BGA framework (`Table` base class)
- **Client:** JavaScript with Dojo Toolkit 1.15 (`ebg/core/gamegui` base class)
- **Database:** MySQL 5.7 (schema in `dbmodel.sql`, managed via BGA's Deck library + raw SQL)
- **Templating:** BGA `.tpl` templates (Smarty-like syntax with `{BLOCK}` substitution)
- **Assets:** CSS sprite sheets for cards (`img/red_cards.jpg`, `img/tree_cards.jpg`)

## File Map

### Core Game Files

| File | Purpose | When to Edit |
|------|---------|--------------|
| `flapjacksandsasquatches.game.php` | **Main game logic.** All server-side rules, player actions, state handlers, card resolution, scoring. | Adding/fixing card effects, changing game rules, fixing turn flow bugs |
| `flapjacksandsasquatches.js` | **Client-side UI.** Renders game state, handles user interaction, animates notifications. | Fixing display bugs, adding animations, changing how cards/trees render |
| `flapjacksandsasquatches.css` | **All styling.** Layout, card sprites, hover effects, animations. | Visual bugs, layout issues, sprite positioning |
| `flapjacksandsasquatches_flapjacksandsasquatches.tpl` | **HTML template.** Static DOM structure: decks, player areas, hand display. JS template strings for dynamic elements. | Adding new UI regions, changing page layout |
| `flapjacksandsasquatches.view.php` | **View controller.** Injects player data into the template (names, colors, positions). | Changing how players are positioned (S/W/N/E) or what data the template receives |

### Configuration & Data

| File | Purpose | When to Edit |
|------|---------|--------------|
| `material.inc.php` | **Card definitions & game constants.** All 100 Jack cards (28 types in `$this->jack_cards`) and 33 tree cards (7 types in `$this->tree_cards`). Also: `card_backs` sprite config, `game_constants` (winning_score, dice thresholds, axe mechanics), and `card_locations` mapping. | Adding/changing cards, adjusting balance constants |
| `states.inc.php` | **State machine definition.** All 13 game states with transitions, descriptions, action methods, and args methods. | Changing turn flow, adding new game phases |
| `dbmodel.sql` | **Database schema.** 6 custom tables + player table extensions. | Adding new persistent data, new tracking tables |
| `stats.json` | **Statistics tracking.** Table-level and player-level stats shown at end of game. | Adding new stats to track |
| `gameinfos.inc.php` | **BGA metadata.** Player count, colors, estimated duration, game classification. | Changing player count or game metadata |
| `gameoptions.json` | **Game variants.** Currently empty (no variants). | Adding game variant options |
| `gamepreferences.json` | **Player preferences.** Currently empty. | Adding client-side preference toggles |

### Other Files

| File | Purpose |
|------|---------|
| `modules/php/Game.php` | Empty module placeholder (required by BGA framework) |
| `img/red_cards.jpg` | Sprite sheet for all red (Jack) cards - 9 columns wide |
| `img/tree_cards.jpg` | Sprite sheet for all tree cards - 4 columns wide |
| `rules.md` | Game rules reference |
| `IMPLEMENTATION_PLAN.md` | Detailed phased implementation plan |
| `TODO.md` | Implementation status tracking |
| `BGA_PLATFORM_GUIDE.md` | BGA platform conventions and API reference |

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    CLIENT (Browser)                      │
│                                                         │
│  flapjacksandsasquatches.js          .css     .tpl      │
│  ├─ setup() / updateDisplay()     (styling) (structure) │
│  ├─ State handlers (onEntering*)                        │
│  ├─ Action senders (bgaPerformAction)                   │
│  └─ Notification handlers (notif_*)                     │
│         │                    ▲                           │
└─────────┼────────────────────┼───────────────────────────┘
          │ actions             │ notifications
          ▼                    │
┌─────────────────────────────────────────────────────────┐
│                    SERVER (PHP)                          │
│                                                         │
│  flapjacksandsasquatches.game.php                       │
│  ├─ Player actions: act*()     ← validates & executes   │
│  ├─ State actions: st*()       ← automatic transitions  │
│  ├─ State args: arg*()         ← data for client states │
│  ├─ getAllDatas()               ← full state on refresh  │
│  └─ Notifications              → pushes updates         │
│         │                                               │
│  material.inc.php  ← card data    states.inc.php ← FSM │
│         │                                               │
└─────────┼───────────────────────────────────────────────┘
          │
          ▼
┌─────────────────────────┐
│     MySQL (dbmodel.sql) │
│  card, active_tree,     │
│  cut_tree, player_*     │
└─────────────────────────┘
```

## State Machine (Turn Flow)

Each turn follows this sequence of states. The state IDs, names, and transitions are defined in `states.inc.php`. The handler functions live in `flapjacksandsasquatches.game.php`.

```
10: playerTurnStart         ─── Does player have a tree?
    ├─ No  → 11: drawTreeCard       ─── Auto-draw from tree deck
    │             └─→ 20
    └─ Yes → 20: drawJackCard       ─── Auto-draw from Jack deck
                  └─→ 30: playerTurn        ─── PLAYER DECIDES: play or discard
                       ├─ discard → 50
                       └─ play   → 31: selectTarget     ─── Pick target (if card needs one)
                                      └─→ 32: checkReaction   ─── Can anyone react?
                                           ├─ yes → 33: reactionWindow    ─── ALL opponents may react
                                           │             └─→ 34: resolveReaction
                                           │                  ├─ blocked → 50
                                           │                  └─ proceed → 40
                                           └─ no  → 40: resolveCard       ─── Execute card effect
                                                     ├─→ 41: sasquatchSighting
                                                     ├─→ 42: contestRoll
                                                     └─→ 50: choppingRoll     ─── Roll dice for chops
                                                              └─→ 60: checkTreeComplete
                                                                   ├─ done → 61: treeCompleted
                                                                   │         ├─→ 99: gameEnd
                                                                   │         └─→ 70
                                                                   └─ not  → 70: nextPlayer → 10
```

**State types:**
- `game` states (10, 11, 20, 32, 34, 40-42, 50, 60, 61, 70) run automatically via `st*()` methods
- `activeplayer` states (30, 31) wait for the active player's input
- `multipleactiveplayer` states (33, 41) wait for input from multiple players simultaneously

## Key Patterns

### Server-side action flow

1. Player clicks something in the UI
2. JS calls `bgaPerformAction('actionName', {params})`
3. PHP method `actActionName()` runs, decorated with `#[ActionParam]` attributes
4. Method calls `checkAction()` to verify the action is legal in the current state
5. Method updates database state
6. Method sends notifications via `self::notifyAllPlayers()` / `self::notifyPlayer()`
7. Method calls `$this->gamestate->nextState('transitionName')` to advance

### Client-side notification flow

1. Server sends notification (e.g., `'treeCompleted'`)
2. JS handler `notif_treeCompleted(notif)` fires
3. Handler reads `notif.args` for data (player_id, tree_type, points, etc.)
4. Handler updates DOM (removes tree display, updates score counter)

### Card data lookup

Cards are identified by `card_type` (`'jack'` or `'tree'`) and `card_type_arg` (index into the material arrays). Two helper methods handle lookups:

```php
// Get the material definition (name, tooltip, sprite, etc.) for a card
$card = $this->cards->getCard($card_id);           // BGA Deck: id, type, type_arg, location
$def = $this->getCardDefinitionForCard($card);      // material.inc.php definition
$def = $this->getCardDefinitionForCardId($card_id); // shorthand: pass just the ID

// Under the hood, switches on card['type']:
//   'jack' → $this->jack_cards[$card['type_arg']]
//   'tree' → $this->tree_cards[$card['type_arg']]
```

```javascript
// In .js
// Cards are passed from server with merged info via getAllDatas() or notifications
```

### Global state variables

Three game-state labels are used (defined in `__construct()`):

| Label | ID | Purpose |
|-------|----|---------|
| `current_round` | 10 | Tracks the current round number |
| `reaction_pending_card` | 11 | Card ID that is waiting for reactions |
| `reaction_pending_player` | 12 | Player ID who played the pending card |

Access via `self::getGameStateValue('current_round')` / `self::setGameStateValue(...)`.

## Database Schema

The `card` table is managed by BGA's Deck library. The other 5 tables are custom.

### Player table extensions (`ALTER TABLE player`)

| Column | Type | Purpose |
|--------|------|---------|
| `player_skip_next_turn` | tinyint(1) | Flag: skip this player's next turn |
| `player_has_drawn_rampage` | tinyint(1) | Flag: player has drawn a rampage card |
| `player_give_hand_to_player_id` | int | Target player for hand-swap effects |
| `player_axe_throw_bonus` | int | Bonus dice for axe throw actions |
| `player_current_tree_id` | int | FK to `active_tree.tree_id` |

### Custom tables

| Table | Key Columns | Purpose |
|-------|-------------|---------|
| `card` | card_type, card_type_arg, card_location, card_location_arg | BGA Deck library storage. Location tracks where each card is (deck, hand, limbo, discard, etc.) |
| `active_tree` | player_id, tree_type, chop_count, chops_required, points_value | Tree currently being chopped by a player |
| `cut_tree` | player_id, tree_type, points_value, cut_order | Completed trees in a player's score pile |
| `player_equipment` | player_id, card_id, equipment_type | Equipment cards in play (axes, gloves, boots) |
| `player_help` | player_id, card_id, help_type | Help cards in play (Apprentice, Long Saw, Babe) |
| `player_modifiers` | player_id, modifier_type, modifier_value, is_persistent | Active plus/minus modifiers (Flapjacks +2, Blisters -1, etc.) |

## Common Tasks

### "I need to implement a new card effect"

1. Define the card in `material.inc.php` → `$this->jack_cards` array (id, type, name, tooltip, sprite_position, qty, targeted, and any card-specific fields like `blockable_by`, `modifier`, `persistent`)
2. Add effect logic in `flapjacksandsasquatches.game.php` → `stResolveCard()` method (switch on card definition type)
3. If the card needs new database state, add columns/tables in `dbmodel.sql`
4. Send a notification from PHP so the client can animate the effect
5. Add a `notif_*` handler in `flapjacksandsasquatches.js` to update the UI
6. If the card has a new visual, update sprite positioning in `.css`

### "I need to fix a turn flow bug"

1. Check `states.inc.php` for the state definitions and transitions
2. Find the corresponding `st*()` method in `flapjacksandsasquatches.game.php`
3. Verify the transition name passed to `$this->gamestate->nextState()` matches a key in the state's `transitions` array
4. Check `onEnteringState()` in the JS for client-side state setup issues

### "The UI is rendering wrong"

1. Check `.tpl` for the HTML structure
2. Check `.css` for styling (sprite positions use `background-position` with card dimensions 120x167px)
3. Check `setup()` and `updateDisplay()` in `.js` for initial rendering
4. Check `notif_*` handlers for dynamic update issues
5. Sprite sheets: red cards are 9 columns wide, tree cards are 4 columns wide

### "I need to add a new statistic"

1. Add the stat definition to `stats.json` (with a unique ID, name, and type)
2. Call `self::incStat(1, 'stat_name')` or `self::incStat(1, 'stat_name', $player_id)` in game.php where the event occurs

### "I need to change the state machine"

1. Add/modify the state in `states.inc.php` (with name, type, description, transitions)
2. If `type` is `"game"`, add an `st*()` handler in game.php
3. If `type` is `"activeplayer"` or `"multipleactiveplayer"`, add `possibleactions`, an `arg*()` method, and corresponding `act*()` methods
4. Update `onEnteringState()` in the JS to handle the new state name
5. Update any existing `nextState()` calls that should transition to the new state

## Client-Side UI Structure

The `.tpl` defines three main regions:

1. **Center area** (`#fns_game_area_center`) - Tree deck, Jack deck, and discard pile
2. **Player areas** (`#fns_player_{PLAYER_ID}_area`) - Each player's tree, equipment, and cut pile, positioned in S/W/N/E directions
3. **Current player's hand** (`#fns_my_hand`) - Card stock managed by `ebg/stock`

Card stocks use BGA's `ebg/stock` module, configured in `setupStocks()` with sprite dimensions and background image references. Cards are 120x167px.

## Development Workflow

- **Edit locally**, sync to BGA Studio via SFTP (`.vscode/sftp.json` is configured)
- **No build step** - PHP is interpreted, JS/CSS are served directly (BGA minifies in production)
- **Test** by starting games in BGA Studio's test environment
- **Database changes** (`dbmodel.sql`) require restarting the game to take effect
- **Zombie handling** (`zombieTurn()` in game.php) handles disconnected players
