*** Settings ***
Resource				../Common.robot
Suite Setup			Login	Umpire	1111
Test Setup			Create Card With Player		test.division1.aardvarks1.bears2
Suite Teardown	Close Browser

*** Test Cases ***
Guest Can Add Note To Card

Guest Can Add Signature To Card

*** Keywords ***
Create Card With Player
	[Arguments]			${fixtureid}
	Reset Card		${fixtureid}
	Open Card			${fixtureid}
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
	Go To						${BASE}/Report/Card?key=test.division1.aardvarks1.bears2
	Comment					${description}
	Page Should Contain Element		xpath://tr[@data-description='${description}']

