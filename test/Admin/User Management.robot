*** Settings ***
Resource				../Common.robot
Suite Setup			Secretary Login		admin		1234
Test Setup			Go To User Admin Page		
Suite Teardown	Close Browser

*** Test Cases ***
Admin User Can View Users
	Page Should Contain Element		id:users-table

Admin User Can Add A User And Cancel
	Click Button	jquery::contains('Add User')
	Click Link		id:add-user
	Click Button	Close

Admin User Can Add A Secretary User
	${username}		Generate Username
	Click Button	jquery::contains('Add User')
	Click Link		id:add-secretary
	Input Text		name:email		${username}
	Select From List By Label			name:club		Aardvarks
	Click Button									id:create-user
	Find User											${username}
	Delete User										${username}
	
Admin User Can Add An Umpire User
	${username}		Generate Username
	Click Button	jquery::contains('Add User')
	Click Link		id:add-umpire
	Input Text		name:username		${username}
	Input Text		name:email		${username}
	Click Button									id:create-user
	Find User											${username}
	Delete User										${username}
	
*** Keywords ***
Go To User Admin Page
	Go To							http://${HOST}/users

Find User
	[Arguments]			${username}	
	Sleep						1s		Waiting for table to refresh
	Input Text			css:input[type=search]		${username}
	Press Key				css:input[type=search]		\\13
	Table Cell Should Contain		users-table		2		1		${username}

Generate Username
	${un1}	Generate Random String
	${username}		Catenate	SEPARATOR=	testuser_		${un1}
	Return From Keyword		${username}

Delete User
	[Arguments]			${username}	
	Click Element		css:tr[data-user=${username}] [href=delete-user]


