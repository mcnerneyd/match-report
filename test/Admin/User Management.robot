*** Settings ***
Resource        ../Common.robot
Suite Setup      Secretary Login    admin    1234
Test Setup      Go To User Admin Page    
Suite Teardown  Close Browser

*** Test Cases ***
Setup
  Generate Username

Admin User Can View Users
  Page Should Contain Element    id:users-table

Admin User Can Add A User And Cancel
  Click Button  jquery::contains('Add User')
  Click Link    id:add-user
  Click Button  Close

Admin User Can Add A Secretary User
  ${email}      Catenate  SEPARATOR=  secretary_    ${base_username}    @nomail.com
  Click Button                  jquery::contains('Add User')
  Click Link                    id:add-secretary
  Input Text                    name:email      ${email}
  Select From List By Label      name:club          Aardvarks
  Click Button                  id:create-user
  Find User                      ${email}
  
Admin User Can Delete A Secretary User
  ${email}      Catenate  SEPARATOR=  secretary_    ${base_username}    @nomail.com
  Find User                      ${email}
  Delete User                    ${email}
  
Admin User Can Add An Umpire User
  ${username}    Catenate  SEPARATOR=  umpire_    ${base_username}
  ${email}      Catenate  SEPARATOR=  secretary_    ${base_username}    @nomail.com
  Click Button    jquery::contains('Add User')
  Click Link      id:add-umpire
  Input Text      name:username    ${username}
  Input Text      name:email      ${email}
  Click Button    id:create-user
  Find User        ${username}
  
Admin User Can Delete An Umpire User
  ${username}    Catenate  SEPARATOR=  umpire_    ${base_username}
  Find User        ${username}
  Delete User      ${username}
  
*** Keywords ***
Go To User Admin Page
  Go To              http://${HOST}/users

Find User
  [Arguments]      ${username}  
  Sleep            1s    Waiting for table to refresh
  Input Text      css:.container input[type=search]    ${username}
  Press Keys        css:.container input[type=search]    RETURN
  Table Cell Should Contain    id:users-table    2    1    ${username}

Generate Username
  ${un1}  Generate Random String
  ${un2}    Catenate  SEPARATOR=  testuser_    ${un1}
  Set Global Variable      ${base_username}    ${un2}

Delete User
  [Arguments]      ${username}  
  Log              Deleting User ${username}
  Click Element    css:tr[data-user='${username}'] [href=delete-user]


