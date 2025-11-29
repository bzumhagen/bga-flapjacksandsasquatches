# Flapjacks and Sasquatches - BGA Implementation Plan

## Project Overview
Implementation of the card game "Flapjacks and Sasquatches" on Board Game Arena platform.

**Game:** Flapjacks and Sasquatches  
**Designer:** William Sininger  
**Players:** 2-8  
**Target Completion:** Multiple phases across several commits

---

## Current Status Assessment

### ✅ Completed
- Basic BGA project structure created
- Game metadata configured (gameinfos.inc.php)
- Red cards material defined (40 card types in material.inc.php)
- Game rules documented (rules.md)
- Card sprite images available (red_cards.jpg, tree_cards.jpg)
- Basic JavaScript framework setup
- Basic state machine skeleton (states.inc.php)

### ❌ Missing/Incomplete
- Tree cards material definition
- Database schema (no tables defined yet)
- Game logic implementation (setupNewGame, core game loop)
- Complete state machine implementation
- Card deck management using BGA Deck library
- Dice rolling mechanism
- Chop token tracking
- Player action handlers
- Client-side UI implementation
- Game statistics configuration
- Complete notification system

---

## Clarifying Questions - ✅ ALL ANSWERED

All design questions have been answered. Summary below:

### 1. **Tree Deck Composition** ✅ ANSWERED
- **Answer:** Seven unique tree types:
  - Norway Pine: 4 chops, 4 points
  - American Elm: 8 chops, 8 points
  - Cottonwood: 5 chops, 6 points
  - Mighty Oak: 9 chops, 12 points
  - Red Oak: 6 chops, 7 points
  - River Birch: 4 chops, 5 points
  - Silver Maple: 7 chops, 8 points
- Multiple copies of each type exist (exact quantities to be determined later)

### 2. **Chop Tokens & Physical Dice** ✅ ANSWERED
- **Answer:** Visual dice rolling animation will be implemented
  - Use animated dice sprites/CSS animations
  - Display individual die results with color coding (breaks, misses, chops)
  - Separate visual display for Apprentice/Babe rolls
- **Chop Tokens:** Will use visual tokens on tree cards with numerical counter backup

### 3. **Card Interactions & Timing** ✅ ANSWERED
- **Answer:** Reaction cards can be played by ANY player (not just the target)
- **Implementation Strategy:**
  - After any card is played, check all players' hands for possible reaction cards
  - Enter a `reactionWindow` state (multipleactiveplayer) where:
    - Debunk: Any player holding it can play if a Sasquatch card was just played
    - Paperwork: Any player holding it can play if Switch Tags or Tree Hugger was just played
    - Northern Justice: Any player holding it can play if Steal Axe or Steal Equipment was just played
  - Players can choose to "Pass" (don't react) or play their reaction
  - Once all players pass or one reaction is played, resolve the card effect
  - Reaction cards include "draw replacement" effect automatically

### 4. **"Give Me a Hand" Card Mechanics** ✅ ANSWERED
- **Answer:** Give Me a Hand creates a separate roll BEFORE the target's main chopping roll
- **Mechanics:**
  - On the target player's next turn, they perform TWO rolls:
    1. **Give Me a Hand roll:** Roll 1 die separately, result goes to the card player's tree
    2. **Normal chopping roll:** Then roll their normal chopping dice for their own tree
  - The Give Me a Hand die:
    - Is taken from their normal dice pool (reduces main roll by 1 die)
    - Rolls separately and applies to the card player's tree
    - 4-6 = adds a chop to card player's tree
    - 1-3 = no effect on card player's tree
    - Does NOT count toward axe breaks for the target player
  - After the Give Me a Hand roll is resolved, the target player continues with their normal turn

### 5. **Long Saw & Partner Passing** ✅ ANSWERED
- **Answer:** Passes immediately when 4+ misses/breaks occur during a roll
- **Mechanics:**
  - If a player rolls 4 or more combined misses (3s) and breaks (1-2s) with Long Saw & Partner
  - The card immediately passes to the player on their RIGHT
  - The current player STILL gets any chops (4-6s) from that roll applied to their tree
  - The receiving player can use Long Saw & Partner on their next turn
- **Example:** Roll 5 dice, get [1, 2, 3, 6, 6] = 2 breaks, 1 miss, 2 chops
  - Player gets 2 chops on their tree
  - Long Saw passes to player on right (4+ misses/breaks total)

### 6. **Contest Cards Resolution** ✅ ANSWERED
- **Answer:** Ties are re-rolled until there is a definite winner
- **Timing:** Contest cards can ONLY be played on your own turn (not as reactions)
- **Mechanics:**
  - Both players roll 1 die
  - If tied, both players immediately re-roll
  - Continue re-rolling until one player wins
  - Apply the contest result based on card type:
    - Log Rolling: Lower roller loses next turn
    - Speed Climb: Higher roller wins 2 victory points immediately
    - Axe Throw: Higher roller gets +2 dice on next chopping roll
    - Chainsaw Carving: Higher roller wins chainsaw equipment card

### 7. **Game Variant Options** ✅ ANSWERED
- **Answer:** Stick to base rules only, no variants
- **Configuration:**
  - Winning score: 21 points (fixed)
  - Starting hand: 3 cards (fixed)
  - No game options to implement in `gameoptions.inc.php`

### 8. **Multiplayer Scaling** ✅ ANSWERED
- **Answer:** No adjustments based on player count
- **Configuration:**
  - Same deck composition for all player counts (2-8 players)
  - Same rules apply regardless of player count
  - No scaling needed

---

## Phase 1: Core Infrastructure (3-5 commits)

### 1.1 Database Schema Design
**File:** `dbmodel.sql`

**Tasks:**
- Create `card` table for deck management
  - card_id, card_type, card_type_arg, card_location, card_location_arg
- Create `tree` table for active trees
  - tree_id, player_id, tree_type, chop_count
- Create `player_equipment` table
  - player_id, equipment_type, equipment_id
- Add player table extensions:
  - player_skip_next_turn (boolean)
  - player_has_drawn_rampage (boolean for Sasquatch Rampage tracking)
  - player_give_hand_to_player_id (int, null if no Give Me a Hand effect active)
  - player_axe_throw_bonus (int, +2 dice from winning Axe Throw contest)

**Files Modified:**
- `dbmodel.sql`

**Estimated Complexity:** Medium

---

### 1.2 Material Definition - Complete Card Data
**File:** `material.inc.php`

**Tasks:**
- Define tree card array with all tree types (NEEDS INFO - see Question 1)
- Define card counts for each red card type
- Define dice roll outcomes (1-6 mapping to break/miss/chop)
- Add constants for game rules (winning score: 21, starting hand: 3, etc.)

**Files Modified:**
- `material.inc.php`

**Estimated Complexity:** Low-Medium

---

### 1.3 Game Setup Implementation
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- Implement `setupNewGame()`:
  - Initialize card decks using BGA Deck library
  - Shuffle and create Tree Deck
  - Shuffle and create Jack Deck
  - Deal 3 cards to each player
  - Initialize player stats
  - Set game state labels
- Implement `getAllDatas()`:
  - Return player hands
  - Return visible cards (player areas, discard pile)
  - Return deck counts
  - Return player equipment and trees
  - Return chop counts

**Files Modified:**
- `flapjacksandsasquatches.game.php`

**Estimated Complexity:** Medium-High

---

## Phase 2: Game State Machine (2-4 commits)

### 2.1 State Machine Design
**File:** `states.inc.php`

**States to Implement:**
1. `gameSetup` (existing) → `playerTurn`
2. `playerTurn` - Main player turn state
3. `drawTreeCard` - Player draws tree if they don't have one (auto-state)
4. `drawJackCard` - Player draws from Jack Deck (auto-state)
5. `playCard` - Player plays a card or discards
6. `reactionWindow` - **MULTIPLEACTIVEPLAYER** state where all players can react
   - Check each player's hand for valid reactions (Debunk/Paperwork/Northern Justice)
   - Allow simultaneous "Pass" or play reaction card
   - First reaction played cancels the original card
   - Once all pass or one reacts, transition to resolution
7. `targetSelection` - Player selects target for card effect
8. `resolveCardEffect` - Handle card effect (may trigger sub-states)
9. `choppingRoll` - Player rolls dice for chopping
10. `contestRoll` - Both players roll for contest cards (could be multipleactiveplayer)
11. `nextPlayer` - Advance to next player, handle skip turns
12. `checkWinCondition` - Check if any player has 21+ points
13. `gameEnd` (existing)

**Sub-states for Special Cards:**
- `sasquatchSighting` - All players roll save (multipleactiveplayer or auto-resolve)
- `sasquatchMatingSeasonTarget` - Target selection
- `giveMeAHandRoll` - Target player rolls 1 die for card player's tree BEFORE their main roll
  - State entered at start of target's turn if they have "Give Me a Hand" effect
  - Roll 1 die, apply to beneficiary's tree if 4-6
  - Then transition to normal chopping roll (with -1 die)
- `longSawBreakCheck` - Check if Long Saw breaks on 4+ misses/breaks

**Files Modified:**
- `states.inc.php`

**Estimated Complexity:** High

---

### 2.2 State Transition Logic
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- Implement state action methods (st*):
  - `stDrawTreeCard()` - Auto-draw if no tree
  - `stDrawJackCard()` - Auto-draw from deck
  - `stChoppingRoll()` - Calculate eligible dice, perform roll
  - `stNextPlayer()` - Handle skip turns, advance player
  - `stCheckWinCondition()` - Check for winner
- Implement state argument methods (arg*):
  - `argPlayCard()` - Return playable cards
  - `argTargetSelection()` - Return valid targets
  - `argChoppingRoll()` - Return dice configuration

**Files Modified:**
- `flapjacksandsasquatches.game.php`

**Estimated Complexity:** High

---

## Phase 3: Player Actions (4-6 commits)

### 3.1 Basic Card Actions
**Files:** `flapjacksandsasquatches.action.php`, `flapjacksandsasquatches.game.php`

**Tasks:**
- `playCard($card_id, $target_player_id = null)`
  - Validation (card in hand, correct state)
  - Card type routing
  - Unique card check (only one Axe, etc.)
- `discardCard($card_id)`
- `passAction()`

**Estimated Complexity:** Medium

---

### 3.2 Equipment Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playEquipmentCard($player_id, $card_id)`
  - Remove existing equipment of same type
  - Place in player equipment area
  - Special handling for Dull Axe (can target others)
- Equipment types to handle:
  - Axes (Chopping, Carpenter's, Swedish Broad, Double Bladed, Titanium, Dull)
  - Gloves
  - Boots
  - Chainsaw (from contest)

**Estimated Complexity:** Medium

---

### 3.3 Plus/Minus Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playPlusMinusCard($player_id, $card_id, $target_player_id)`
  - Flapjacks (+2 dice, self only, one-time)
  - Shortstack (+1 die, self only, one-time)
  - Side of Bacon (+1 die, stackable with Flapjacks/Shortstack)
  - Winded (-2 dice, target player, one-time)
  - Footslip (-2 dice, target player, one-time, preventable by Boots)
  - Axe Slip (-1 die, target player, one-time, preventable by Gloves)
  - Blisters (-1 die, target player, persistent, removable by Gloves)

**Estimated Complexity:** Medium-High

---

### 3.4 Help Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playHelpCard($player_id, $card_id)`
  - Apprentice (separate 1-die roll, no break contribution)
  - Babe (separate 2-dice roll, can be lured)
  - Long Saw & Partner (5 dice, replaces axe, break on 4+ misses/breaks) (NEEDS CLARIFICATION - see Question 5)

**Estimated Complexity:** Medium

---

### 3.5 Sasquatch Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playSasquatchCard($player_id, $card_id, $target_player_id = null)`
  - Sasquatch Sighting (all others roll save) (NEEDS CLARIFICATION - see Question 3 for timing)
  - Sasquatch Mating Season (target loses turn, steal tree)
  - That Darn Sasquatch (discard all equipment)
  - Sasquatch Rampage (all discard hands, draw new on next turn)
- `checkSasquatchDebunk()` - Window for Debunk card (NEEDS CLARIFICATION - see Question 3)
- All Sasquatch cards discard Help cards unless Debunked

**Estimated Complexity:** High

---

### 3.6 Action Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playActionCard($player_id, $card_id, $target_player_id = null, $target_card_id = null)`
  - Paul Bunyan (all trees → cut piles)
  - Forest Fire (discard all trees + chop tokens)
  - Beavers (discard target player's tree)
  - Switch Tags (swap cut trees) (NEEDS CLARIFICATION - see Question 3 for Paperwork timing)
  - Tree Hugger (target loses turn) (NEEDS CLARIFICATION - see Question 3 for Paperwork timing)
  - Axe Break (discard target axe, not Titanium)
  - Steal Equipment (take target equipment)
  - Steal Axe (take target axe)
  - Lure Help (steal Apprentice or Long Saw & Partner)
  - Babe Biscuit (lure Babe from opponent or discard)
  - Give Me a Hand (target rolls 1 die for your tree on their next turn, reducing their dice by 1)

**Estimated Complexity:** High

---

### 3.7 Contest Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playContestCard($player_id, $card_id, $target_player_id)`
  - Can only be played on your own turn (not as reaction)
  - Transitions to `contestRoll` state
- `performContestRoll($player1_id, $player2_id, $contest_type)`
  - Both players roll 1 die
  - If tied, re-roll immediately (loop until winner determined)
  - Apply results based on contest type:
    - Log Rolling: Lower roller sets skip_next_turn flag
    - Speed Climb: Higher roller gains 2 points immediately (no tree needed)
    - Axe Throw: Higher roller sets axe_throw_bonus to +2
    - Chainsaw Carving: Higher roller receives chainsaw equipment card
- Contest results are instant and cannot be blocked/reacted to

**Estimated Complexity:** Medium

---

### 3.8 Reaction Card Handlers
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `playReactionCard($player_id, $card_id, $target_card_id)`
  - Debunk (stops Sasquatch, both discarded, draw replacement)
  - Paperwork (stops Tree Hugger/Switch Tags, both discarded, draw replacement)
  - Northern Justice (stops Steal Axe/Steal Equipment, stealer loses chopping roll)
- `checkReactionPossible($card_played)` - Determine which players can react
  - Return array of players who have valid reaction cards
  - Map card types to valid reactions:
    - Sasquatch cards → Debunk
    - Switch Tags/Tree Hugger → Paperwork
    - Steal Axe/Steal Equipment → Northern Justice
- `enterReactionWindow($original_card)` - Transition to multipleactiveplayer state
  - Set all eligible players as active
  - Store original card info in game state
  - Players can "Pass" or "PlayReaction"
- `resolveReaction($reaction_played)` - Handle reaction effects
  - Discard both original and reaction card
  - Draw replacement card for reaction player
  - Cancel original card effect or apply penalty (Northern Justice)

**Estimated Complexity:** High

---

## Phase 4: Dice Rolling & Chopping (2-3 commits)

### 4.1 Dice Roll Implementation
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `performChoppingRoll($player_id, $for_tree_owner = null)`
  - Calculate total dice (axe + plus/minus modifiers)
  - Check for "Give Me a Hand" effect (reduces dice by 1)
  - Roll dice using BGA's `bga_rand(1, 6)` for each die
  - Interpret results:
    - 1-2: Break
    - 3: Miss
    - 4-6: Chop
  - Check for axe break (3+ breaks in one roll)
  - Apply chops to tree (either own tree or specified tree owner)
  - Handle Apprentice separate roll (1 die, separate from breaks)
  - Handle Babe separate rolls (2 dice, separate from breaks)
  - Handle Long Saw & Partner special break condition:
    - Count misses (3s) and breaks (1-2s)
    - If total >= 4, pass card to player on right
    - Still apply any chops from this roll first
- `performGiveMeAHandRoll($target_player_id, $beneficiary_player_id)`
  - Roll 1 die for the beneficiary's tree
  - Apply result if 4-6
  - Notify both players
  - Set flag to reduce target's main roll by 1 die

**Estimated Complexity:** Medium-High

---

### 4.2 Chop Token Management
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- `addChopsToTree($player_id, $chop_count)`
- `checkTreeComplete($player_id)`
  - If chops >= required, move tree to cut pile
  - Award points
  - Update score
  - Check win condition

**Estimated Complexity:** Medium

---

## Phase 5: Client-Side Implementation (4-6 commits)

### 5.1 Game Board Layout
**Files:** `flapjacksandsasquatches_flapjacksandsasquatches.tpl`, `flapjacksandsasquatches.css`

**Tasks:**
- Design main play area layout:
  - Center: Tree Deck, Jack Deck, Discard Pile
  - Player areas: Equipment zone, Active tree, Cut pile, Hand (current player only)
  - Dice roll area
- Create HTML templates for:
  - Card elements
  - Tree cards with chop token displays (NEEDS CLARIFICATION - see Question 2)
  - Equipment slots
  - Dice (if visual) (NEEDS CLARIFICATION - see Question 2)

**Estimated Complexity:** Medium-High

---

### 5.2 Card Stock Management
**File:** `flapjacksandsasquatches.js`

**Tasks:**
- Initialize card stocks:
  - Player hand (current player)
  - Tree deck
  - Jack deck
  - Discard pile
  - Each player's equipment area
  - Each player's cut pile
- Implement card image mapping to sprites
- Handle card selection and drag-drop (if applicable)
- Implement card tooltips with full card text

**Estimated Complexity:** Medium

---

### 5.3 Player Action Interface
**File:** `flapjacksandsasquatches.js`

**Tasks:**
- `onPlayCard(evt)` - Handle card play from hand
- `onSelectTarget(evt)` - Handle target selection for cards
- `onRollDice(evt)` - Trigger chopping roll
- `onPass(evt)` - Pass turn / discard
- Action button management based on game state
- Highlight valid targets
- Highlight playable cards

**Estimated Complexity:** Medium-High

---

### 5.4 Notifications & Animations
**File:** `flapjacksandsasquatches.js`

**Tasks:**
- Implement notification handlers:
  - `notif_cardPlayed` - Animate card from hand to play area
  - `notif_cardDrawn` - Animate card from deck to hand
  - `notif_diceRolled` - Show dice roll results (NEEDS CLARIFICATION - see Question 2)
  - `notif_treeChopped` - Update chop tokens
  - `notif_treeComplete` - Move tree to cut pile
  - `notif_equipmentPlayed` - Show equipment in player area
  - `notif_equipmentStolen` - Move equipment between players
  - `notif_sasquatchPlayed` - Special animation for Sasquatch cards
  - `notif_playerSkipsTurn` - Visual indicator
  - `notif_scoreUpdate` - Update score display
- Add card movement animations
- Add sound effects (optional)

**Estimated Complexity:** High

---

### 5.5 Dice Visualization
**File:** `flapjacksandsasquatches.js`, `flapjacksandsasquatches.css`

**Tasks:**
- Create dice rolling animation (CSS keyframes or sprite animation)
- Create dice sprites for values 1-6
- Display dice results with color coding:
  - Red background for breaks (1-2)
  - Yellow/orange background for misses (3)
  - Green background for chops (4-6)
- Separate display areas for:
  - Main chopping roll (from axe/long saw)
  - Apprentice roll (1 die, separate)
  - Babe roll (2 dice, separate)
- Show roll summary (e.g., "3 chops, 1 miss, 1 break")
- Animate dice "rolling" before showing results
- Add sound effects for rolling and results

**Estimated Complexity:** Medium-High

---

## Phase 6: Game Statistics & Polish (2-3 commits)

### 6.1 Statistics Configuration
**File:** `stats.inc.php`

**Tasks:**
- Define table statistics:
  - Average game length
  - Total trees chopped
- Define player statistics:
  - Trees chopped
  - Points earned
  - Sasquatch cards played
  - Reaction cards played successfully
  - Contests won
  - Axes broken
  - Highest single chopping roll

**Estimated Complexity:** Low

---

### 6.2 Game Options
**File:** `gameoptions.inc.php`

**Tasks:**
- Implement game variants (OPTIONAL - see Question 7):
  - Winning score options (15/21/30)
  - Speed mode toggle
- Validate options work correctly

**Estimated Complexity:** Low-Medium

---

### 6.3 Zombie Player Handling
**File:** `flapjacksandsasquatches.game.php`

**Tasks:**
- Implement `zombieTurn()` for all game states
  - Auto-pass or make random valid moves
  - Ensure game can continue if player quits

**Estimated Complexity:** Medium

---

### 6.4 UI/UX Polish
**Files:** `flapjacksandsasquatches.css`, `flapjacksandsasquatches.js`

**Tasks:**
- Responsive design testing
- Mobile layout optimization
- Card hover effects
- Smooth animations
- Loading states
- Error message display
- Colorblind-friendly indicators

**Estimated Complexity:** Medium

---

## Phase 7: Testing & Bug Fixes (Ongoing)

### 7.1 Unit Testing
**Tasks:**
- Test each card type individually
- Test all card interactions
- Test edge cases:
  - Empty decks
  - All players skip turns simultaneously
  - Multiple reaction cards played
  - Axe breaking during critical moments
  - Long Saw & Partner passing chains

**Estimated Complexity:** High

---

### 7.2 Multiplayer Testing
**Tasks:**
- Test with 2, 4, 6, and 8 players
- Test concurrent actions
- Test turn timer behavior
- Test reconnection scenarios

**Estimated Complexity:** Medium-High

---

### 7.3 BGA Pre-Release Checklist
**Tasks:**
- Review BGA pre-release checklist: https://en.doc.boardgamearena.com/Pre-release_checklist
- Fix all validation errors
- Test in BGA Studio
- Submit for alpha testing

**Estimated Complexity:** Medium

---

## Technical Architecture Notes

### BGA Framework Components Used
1. **Deck Library:** For card management (Jack Deck, Tree Deck)
2. **Stock Library:** For card display in UI
3. **Game State Machine:** For turn flow
4. **Notifications:** For real-time updates
5. **Random Functions:** For dice rolling (`bga_rand()`)

### Key Design Decisions
1. **Card Uniqueness:** Track using database to enforce "one per player" rule
2. **Chop Tokens:** Store as integer in database, display in UI
3. **Reaction Timing:** Implement as multipleactiveplayer state (PENDING CLARIFICATION)
4. **Dice Rolling:** Use BGA random functions with notification for results (PENDING CLARIFICATION)

---

## Risk Assessment

### High Risk Areas
1. **Reaction Card Timing** - Complex multiplayer interaction window
2. **Long Saw & Partner Passing** - State management when card moves mid-turn
3. **Sasquatch Sighting** - All players rolling simultaneously
4. **Give Me a Hand** - Dice manipulation between players

### Mitigation Strategies
- Implement and test complex cards last
- Create dedicated test scenarios for each complex card
- Use BGA's multipleactiveplayer state for simultaneous actions
- Extensive logging for debugging

---

## Estimated Timeline

### Per Phase (assuming part-time development)
- **Phase 1:** 1-2 weeks (5-10 hours)
- **Phase 2:** 1-2 weeks (8-12 hours)
- **Phase 3:** 2-3 weeks (15-20 hours)
- **Phase 4:** 1 week (5-8 hours)
- **Phase 5:** 2-3 weeks (15-20 hours)
- **Phase 6:** 1 week (5-8 hours)
- **Phase 7:** Ongoing throughout + 1-2 weeks dedicated (10-15 hours)

**Total Estimated Time:** 8-12 weeks (60-90 hours)

---

## Next Steps

1. **Answer clarifying questions above**
2. **Finalize tree card data** (Question 1 is critical)
3. **Begin Phase 1.1** - Database schema
4. **Set up local BGA Studio environment** if not already done
5. **Create git branches for each phase**

---

## Notes & Decisions Log

### Decision 1: Card Sprite Management
- Using pre-existing sprite sheets (red_cards.jpg, tree_cards.jpg)
- Card dimensions: 72x96 pixels
- 5 items per row in sprite sheet

### Decision 2: BGA Framework Version
- PHP 8.4
- MySQL 5.7
- Dojo Toolkit 1.15

### Decision 3: Dice Visualization
- Using visual dice rolling with CSS animations
- Color-coded results (red=break, yellow=miss, green=chop)
- Separate display areas for main roll, Apprentice, and Babe
- Sound effects for enhanced user experience

### Decision 4: Reaction Card System
- Implemented as multipleactiveplayer state
- Any player can play a reaction card (not just target)
- System checks all hands after each card play
- First reaction played cancels original card
- Automatic card draw replacement for reaction player

### Decision 5: "Give Me a Hand" Mechanics
- Separate roll that occurs BEFORE the target's normal chopping roll
- Target rolls 1 die for the card player's tree
- Die is taken from target's normal dice pool (reduces their main roll by 1)
- Result only helps card player's tree (4-6 = chop, 1-3 = nothing)
- Does NOT count toward axe breaks for the target player
- Stored in database as `player_give_hand_to_player_id` field

### Decision 6: Long Saw & Partner Passing
- Passes immediately when 4+ misses/breaks occur in a single roll
- Current player still gets chops from that roll before passing
- Receiving player (on right) can use it on their next turn
- Stored in player_equipment table, moved to new player on trigger

### Decision 7: Contest Cards
- Re-roll ties until definite winner
- Can only be played on your own turn (active player only)
- Results are immediate and final (no reactions possible)
- Speed Climb awards 2 VP directly (bypass tree requirement)

### Decision 8: No Game Variants
- Fixed 21 point winning condition
- Fixed 3 card starting hand
- No player count scaling
- Simplifies implementation - no gameoptions.inc.php configuration needed

---

**Last Updated:** 2025-11-29  
**Status:** ✅ ALL QUESTIONS ANSWERED - Ready to Begin Implementation!
