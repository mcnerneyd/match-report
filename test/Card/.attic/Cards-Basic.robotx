*** Settings ***
Resource	Common.robot

*** Test Cases ***
Create A Card
	Login						test	Aardvarks		1234
	Reset Card			D1Aardvarks1Bears2

	# Build team
	Open Card				D1Aardvarks1Bears2
	Select Player		AIGNER
	Select Player		FLUKE
	Select Player		GREGO
	Select Player		HORNAK
	Submit Team

	# Edit players
	Player Menu			AIGNER		Technical - Breakdown
	Player Menu			FLUKE			Technical - Delay/Time Wasting
	Player Menu			GREGO			Technical - Dissent
	Player Menu			AIGNER		No Cards
	Player Menu			AIGNER		Physical - Tackle
	Player Menu			HORNAK		Red Card
	Player Menu			AIGNER		Scored
	Player Menu			AIGNER		Scored
	Player Menu			AIGNER		Scored
	Element Text Should Be		jquery=#matchcard-home .score		3
	Player Menu			AIGNER		No Score
	Player Menu			HORNAK		Scored
	Player Menu			GREGO			Scored
	Element Text Should Be		jquery=#matchcard-home .score		2

	# Add a player
	Execute Javascript							scrollTo(0,500)
	Sleep						2 seconds
	Click Element		xpath=(//a[@class='add-player'])[1]
	Wait Until Element Is Visible	player-name
	Input Text			player-name		Barry Blue Sky
	Click Element		jquery=.modal-footer .btn-success

	Submit Card			Judge McUmpire		3
	Sleep						3 seconds
	Element Should Contain		id=countdown		Card will auto

	# Verify that the result exists
	Go To Matches
	Click Element		partial link=Results
	Element Should Be Visible		jquery=[data-key='D1Aardvarks1Bears2']
	Close Browser

Opposition Card
	Login						test	Bears		1111

	# Build team
	Open Card				D1Aardvarks1Bears2
	Select Player		DEALBA
	Select Player		DENG
	Select Player		BURRUS
	Select Player		FERRELL
	Select Player		GRISWOLD
	Select Player		ING
	Select Player		LOMAX
	Select Player		LOO
	Select Player		RUNKLE
	Submit Team

	# Edit players
	Player Menu			DEALBA		Scored
	Player Menu			DENG			Scored
	Player Menu			BURRUS		Scored
	Player Menu			BURRUS		No Score
	Player Menu			GRISWOLD	Physical - Dangerous/Reckless Play
	Player Menu			ING				Red Card
	Player Menu			LOMAX			Scored
	Player Menu			RUNKLE		Scored
	Element Text Should Be		jquery=#matchcard-away .score		4
	Submit Card			Umpire Jones		5

	# Verify that the result exists
	Go To Matches
	Click Element		partial link=Results
	Element Should Be Visible		jquery=[data-key='D1Aardvarks1Bears2']
	Close Browser

