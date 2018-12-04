*** Settings ***
Resource				../Common.robot
Suite Setup			Login	Aardvarks	1111
Test Setup			Create Card With Player
Suite Teardown	Close Browser

*** Test Cases ***
User Can Add Goal To Player
	Player Menu		GOSHA, Jackeline
	Click Button	Add Goal
	
User Can Clear Goals From Player
	Player Menu		GOSHA, Jackeline		
	Click Button	Clear Goals
	
User Cannot Add Card To Player
	Player Menu		GOSHA, Jackeline		
	Element Should Not Be Visible		class:card-yellow
	Element Should Not Be Visible		class:card-red
	
User Cannot Remove Cards To Player
	Player Menu		GOSHA, Jackeline		
	Element Should Not Be Visible		class:card-clear

User Can Set Player Number
	Player Menu		GOSHA, Jackeline		
	Input Text		name:shirt-number		13
	Click Button	Set
	
All Players Must Have Shirt Numbers
	${username}						Generate Username
	Add Player						${username} Smith
	Wait Until Page Contains		SMITH
	Click Link						link:Submit Card
	Page Should Contain		All players must have a shirt number
	Click Button					OK
	Player Menu						SMITH, ${username}
	Input Text						name:shirt-number		44
	Click Button					Set
	Sleep									1s
	Wait Until Page Contains		Submit Card
	Submit Card

*** Keywords ***
Create Card With Player
	Reset Card		35
	Open Card			35
	Select Player			Jackeline GOSHA
	Submit Team
	Page Should Contain		matchcard has officially appointed umpires

Player Menu
	[Arguments]			${player}
	Click Element		xpath=//tr[@data-name='${player}']

Click Menu
	[Arguments]		${text}
	Click Element		xpath=//*[contains(text(), '${text}')]

Add Player
	[Arguments]		${player}
	Execute Javascript		$.post(baseUrl + "&action=player&ineligible=${player}").done( function() { location.reload(); });

Submit Card
	Click Link			link:Submit Card
	Sleep						1s
	Click Link			jquery:#submit-matchcard a.btn-success		
	Click Button		jquery:#submit-matchcard button.btn-success		

Generate Username
	${un1}	Generate Random String		chars=[LOWER]
	${username}		Catenate	SEPARATOR=	Testuser A	${un1}
	Return From Keyword		${username}

