{OVERALL_GAME_HEADER}

<!--
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- FlapjacksAndSasquatches implementation : © <Your name here> <Your email address here>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

    flapjacksandsasquatches_flapjacksandsasquatches.tpl

    This is the HTML template of your game.

    Everything you are writing in this file will be displayed in the HTML page of your game user interface,
    in the "main game zone" of the screen.

    You can use in this template:
    _ variables, with the format {MY_VARIABLE_ELEMENT}.
    _ HTML block, with the BEGIN/END format

    See your "view" PHP file to check how to set variables and control blocks

    Please REMOVE this comment before publishing your game on BGA
-->

<div id="flapjacksandsasquatches_game_area">
    <!-- CENTER AREA -->
    <div id="center_area" class="whiteblock">
        <div id="tree_deck" class="deck"></div>
        <div id="jack_deck" class="deck"></div>
        <div id="discard_pile" class="deck"></div>
    </div>

    <!-- PLAYER AREAS -->
    <div id="player_areas">
        <!-- BEGIN player -->
        <div id="player_{PLAYER_ID}_area" class="player_area whiteblock playertable_{DIR}">
            <div class="playertablename" style="color:#{PLAYER_COLOR}">
                {PLAYER_NAME}
            </div>
            <div class="player_hand" id="player_hand_{PLAYER_ID}">
                <!-- Player hand will be populated by JavaScript -->
            </div>
            <div class="player_play_area">
                <div class="tree_area" id="tree_area_{PLAYER_ID}"></div>
                <div class="equipment_area" id="equipment_area_{PLAYER_ID}"></div>
                <div class="cut_pile" id="cut_pile_{PLAYER_ID}"></div>
            </div>
        </div>
        <!-- END player -->
    </div>

    <!-- Current player's hand -->
    <div id="myhand_wrap" class="whiteblock">
        <h3>{MY_HAND}</h3>
        <div id="myhand">
        </div>
    </div>
</div>

<script type="text/javascript">
    // Javascript HTML templates

    // Card template for use with BGA Stock
    var jstpl_card = '<div class="card" id="card_${id}" style="background-position:-${x}px -${y}px"></div>';

    // Chop token for tracking progress on trees
    var jstpl_chop_token = '<div class="chop_token" id="chop_token_${player_id}_${number}"></div>';

    // Player board components
    var jstpl_player_board = '<div class="cp_board">\
        <div id="player_board_${id}" class="player_board_content">\
            <div class="player_name" style="color:#${color}">${name}</div>\
            <div class="player_score_wrap">\
                <span class="player_score_label">Points:</span>\
                <span id="player_score_${id}" class="player_score">${score}</span>\
            </div>\
        </div>\
    </div>';

    // Tree card display with chop tracking
    var jstpl_tree = '<div class="tree_card" id="tree_${player_id}" data-tree-id="${tree_id}">\
        <div class="tree_name">${tree_name}</div>\
        <div class="tree_progress">\
            <span class="chops_current" id="chops_current_${player_id}">0</span> / \
            <span class="chops_required">${chops_required}</span>\
        </div>\
        <div class="tree_points">Worth: ${points} points</div>\
        <div class="chop_markers" id="chop_markers_${player_id}"></div>\
    </div>';
</script>

{OVERALL_GAME_FOOTER}
