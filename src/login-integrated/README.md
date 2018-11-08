This is the login-integrated branck of JS-PHP-Based-Groupchat

In order to obtain maximum use from this branch, you will need to have a backend framework already in place.

The following functions and paramaters need to be fed:

  - loggedIn() #Returns True/False to state if user exists
  - isRole()   #Returns role of logged in user
  - $userData  #Mapped array containing information about logged in user (username, role, etc);
  
  DATABASE:
    Table `chat_log`
      - Collumn `id` [INT 9, Primary, Auto increment]
      - Collumn `username` [VARCHAR 30]
      - Collumn `ip` [VARCHAR 46]
      - Collumn `content` [VARCHAR 255] 
      
    Table 
