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
	
User Can Add Technical Yellow Card To Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Technical - Breakdown
	
User Can Add Physical Yellow Card To Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Physical - Tackle
	
User Can Add Red Card To Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Red Card
	
User Can Clear Cards From Player
	Player Menu		GOSHA, Jackeline		
	Click Menu		Red Card
	Wait Until Element Is Not Visible	context-menu
	Player Menu		GOSHA, Jackeline		
	Click Menu		No Cards
	
User Can Set Player Number
	Player Menu		GOSHA, Jackeline		
	Input Text		name:shirt-number		12
	Click Button	Set Number
	

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

