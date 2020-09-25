*** Settings ***
Resource				../Common.robot
Suite Setup			Login	Aardvarks	1111
#Suite Teardown	Close Browser

*** Test Cases ***
User Can Create A Card
	Reset Card		6
	Open Card			6
	Select Player			Jackeline GOSHA
	Select Player			Alia LINDAHL
	Select Player			Kenyatta SHORE
	Select Player			Roma RIVIERA
	Select Player			Lana GLEN
	Select Player			Susy ANDREPONT
	Select Player			Jeffie HOUCK
	Select Player			Kenda MCCALLON
	Select Player			Hortense HELMUTH
	Select Player			Fatimah KUA
	Select Player			Hellen MANRIQUEZ
	Select Player			Chante CLIFFORD
	Select Player			Deena BLAKNEY
	Select Player			Dorotha KNARR
	# Remove some players
	Select Player			Lana GLEN
	Select Player			Hellen MANRIQUEZ
	Select Player			Kenyatta SHORE
	Select Player			Kenda MCCALLON
	# Re-add a player
	Select Player			Hellen MANRIQUEZ
	Submit Team

Add Player To Fixture

Remove Player From Fixture

Add Last Players To Fixture

Clear Players On Fixture
	Reset Card		6
	Open Card			6
	Select Player			Jackeline GOSHA
	Select Player			Alia LINDAHL
	Select Player			Kenyatta SHORE
	Click Link				Clear

Mark Match As Postponed


