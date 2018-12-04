*** Settings ***
Resource				../Common.robot
Suite Setup			Login	Aardvarks	1111
Test Setup			Create Card With Player
Suite Teardown	Close Browser

*** Test Cases ***
User Can Add Goal To Player
	Player Menu		GOSHA, Jackeline
	Click Button	Add Goal
	Submit Card
	Verify Card		Scored GOSHA, Jackeline Aardvarks 1
	
User Can Clear Goals From Player
	Player Menu		GOSHA, Jackeline		
	Click Button	Clear Goals
	Submit Card
	
User Can Add Technical Yellow Card To Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Technical - Breakdown
	Submit Card
	Verify Card		Yellow Card GOSHA, Jackeline Aardvarks Technical - Breakdown
	
User Can Add Physical Yellow Card To Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Physical - Tackle
	Submit Card
	Verify Card		Yellow Card GOSHA, Jackeline Aardvarks Physical - Tackle
	
User Can Add Red Card To Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Red Card
	Submit Card
	Verify Card		Red Card GOSHA, Jackeline Aardvarks Red Card
	
User Can Clear Cards From Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Red Card
	Wait Until Element Is Not Visible	context-menu
	Player Menu		GOSHA, Jackeline		
	Click Menu		No Cards
	Submit Card
	
User Can Set Player Number
	Player Menu		GOSHA, Jackeline		
	Input Text		name:shirt-number		12
	Click Button	Set

*** Keywords ***
Create Card With Player
	Reset Card		6
	Open Card			6
	Select Player			Jackeline GOSHA
	Submit Team

Player Menu
	[Arguments]			${player}
	Click Element		xpath=//tr[@data-name='${player}']

Click Menu
	[Arguments]		${text}
	Click Element		xpath=//*[contains(text(), '${text}')]

Submit Card
	Click Link			link:Submit Card
	Sleep						1s
	Click Link			jquery:#submit-matchcard a.btn-success		
	Click Button		jquery:#submit-matchcard button.btn-success		

Verify Card
	[Arguments]			${description}
	Go To						http://cards.leinsterhockey.ie/cards/fuel/public/Report/Card/6
	Comment					${description}
	Page Should Contain Element		xpath://tr[@data-description='${description}']

