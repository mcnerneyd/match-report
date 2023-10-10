*** Settings ***
Resource				../Common.robot
Suite Setup			Run Keywords	Set Strict
...														Standard Login
										
Test Setup			Create Card With Player
Suite Teardown	Close Browser

*** Test Cases ***
User Can Add Goal To Player
	Player Menu		Jackeline GOSHA
	Click Button	Add Goal
	
User Can Clear Goals From Player
	Player Menu		Jackeline GOSHA
	Click Button	Clear Goals
	
User Cannot Add Card To Player
	Player Menu		Jackeline GOSHA
	Element Should Not Be Visible		class:card-yellow
	Element Should Not Be Visible		class:card-red
	
User Cannot Remove Cards To Player
	Player Menu		Jackeline GOSHA
	Element Should Not Be Visible		class:card-clear

User Can Set Player Number
	Player Menu		Jackeline GOSHA
	Input Text		name:shirt-number		13
  Click Button   css:#set-number button
  Wait Until Element Is Not Visible    css:#context-menu
  Verify Card    home GOSHA, Jackeline 13
	
All Players Must Have Shirt Numbers
	${username}						Generate Username
	Add Player						${username} Smith
	Wait Until Page Contains		SMITH
	${disabled}=						Get Element Attribute		css:#submit-button		disabled
	Should Be Equal				${disabled}		true
	Page Should Contain		there are players without assigned shirt numbers
	Player Menu						${username} SMITH
	Input Text						name:shirt-number		44
  Click Button   				css:#set-number button
	Sleep									1s
	Wait Until Page Contains		Submit Card

*** Keywords ***
Create Card With Player
	Reset Card		35
	Open Card			35
	Select Player			Jackeline GOSHA
	Submit Team
	Page Should Contain		matchcard has officially appointed umpires

Set Strict
	#Secretary Login					admin		password
	#Go To					${BASE}/Admin/Config
	#Input Text		name:strict_comps						d3
	#Click Button	Save
	#Close Browser

Standard Login
	Login					Aardvarks		1111

Player Menu
	[Arguments]			${player}
	Click Element		xpath=//tr[@data-name='${player}']

Click Menu
	[Arguments]		${text}
	Click Element		xpath=//*[contains(text(), '${text}')]

Add Player
	[Arguments]		${player}
  Execute Javascript    window.jQuery("#submit-card .add-player")[0].scrollIntoView(true);
  Click Link      css:#submit-card .add-player
  Sleep           1s
  Click Element   css:#player-name-selectized        # Activate selectize
  Press Keys      css:#player-name-selectized    ${player}
  Click Button    css:#add-player-modal .btn-success
  Sleep           1s

Submit Card
	Click Link			link:Submit Card
	Sleep						1s
	Click Link			jquery:#submit-matchcard a.btn-success		
	Click Button		jquery:#submit-matchcard button.btn-success		

Generate Username
	${un1}	Generate Random String		chars=[LOWER]
	${username}		Catenate	SEPARATOR=	Testuser A	${un1}
	Return From Keyword		${username}

Verify Card
  [Arguments]    ${description}
  Sleep		      1s
  Go To          ${BASE}/Report/Card/35
  Comment        ${description}
  Page Should Contain Element    xpath://tr[@data-description='${description}']

