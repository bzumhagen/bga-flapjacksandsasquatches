# Flapjacks and Sasquatches - TODO Items

## Critical Items

### Card Count Verification
**Status:** PENDING  
**Priority:** Medium  
**Current State:**
- Red Cards (Jack Deck): 100 cards
- Tree Cards (Tree Deck): 33 cards
- **Total: 133 cards**
- **Expected: 136 cards (per game box)**
- **Missing: 3 cards**

**Action Required:**
- Verify official card list from publisher or complete game copy
- Most likely candidates for incorrect counts:
  - Chopping Axe (currently 5, might be 6)
  - Winded (currently 3, might be 4)
  - Flapjacks (currently 3, might be 4)
  - Debunk (currently 3, might be 4)
  - Or any other common card type

**Impact:** Low - game logic works with any quantities defined in material.inc.php

---

## Implementation Status

### Phase 1: Core Infrastructure ✅ COMPLETED
- [x] Database schema (dbmodel.sql)
- [x] Material definitions (material.inc.php) - *pending count verification*
- [x] Game setup logic (flapjacksandsasquatches.game.php)
- [x] Statistics configuration (stats.inc.php)

### Phase 2: Game State Machine ⏳ NEXT
- [ ] Define all game states (states.inc.php)
- [ ] Implement state transition logic
- [ ] Implement state action methods (st*)
- [ ] Implement state argument methods (arg*)

### Phase 3: Player Actions 🔜 PENDING
- [ ] Basic card actions (playCard, discard, pass)
- [ ] Equipment card handlers
- [ ] Plus/minus card handlers
- [ ] Help card handlers
- [ ] Sasquatch card handlers
- [ ] Action card handlers
- [ ] Contest card handlers (N/A for base game)
- [ ] Reaction card handlers

### Phase 4: Dice Rolling & Chopping 🔜 PENDING
- [ ] Dice roll implementation
- [ ] Chop token management
- [ ] Tree completion checks
- [ ] Win condition checks

### Phase 5: Client-Side Implementation 🔜 PENDING
- [ ] Game board layout
- [ ] Card stock management
- [ ] Player action interface
- [ ] Notifications & animations
- [ ] Dice visualization

### Phase 6: Game Statistics & Polish 🔜 PENDING
- [ ] Statistics tracking
- [ ] Game options (N/A - base rules only)
- [ ] Zombie player handling
- [ ] UI/UX polish

### Phase 7: Testing & Bug Fixes 🔜 PENDING
- [ ] Unit testing
- [ ] Multiplayer testing
- [ ] BGA pre-release checklist

---

## Known Issues

*None currently*

---

## Notes

- Base game only (no expansions)
- No game variants
- No player count scaling
- Contest cards (Log Rolling, Speed Climb, Axe Throw, Chainsaw Carving) not in base game
- Expansion cards not in base game: Beavers, Sasquatch Rampage, Double Bladed Axe, Side of Bacon, Babe, Babe Biscuit, Give Me a Hand, Northern Justice

---

**Last Updated:** 2025-11-29
