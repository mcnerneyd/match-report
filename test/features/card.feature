Feature: Matchcard
    In order to submit a matchcard
    As a team captain
    I need to be able to add/edit and remove players, goals, penalties, and roles to a card

    Scenario: Adding a player to a card
        Given there is a card
        When I add the player "Joe Bloggs" to the card
        Then the player should be on the card

    Scenario: Adding a goal to a player
        Given there is a player "Joe Bloggs" on the card
        When I add a goal to the player
        Then the player is listed as having a goal

    Scenario: Adding a penalty to a player
        Given there is a player "Joe Bloggs" on the card
        When I add a red card to the player
        Then the player is listed as having a red card

    Scenario: Setting a role on a player
        Given there is a player "Joe Bloggs" on the card
        When I set the captain role on the player
        Then the player is listed as captain

    