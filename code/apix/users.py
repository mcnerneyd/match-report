from fastapi import Depends, APIRouter
from sqlalchemy.orm import Session
from sqlalchemy import text
from db import get_db

router = APIRouter(prefix="/users")

def group(group_id: int) -> str:
    if (group_id >= 100):
        return "superuser"
    if (group_id >= 99):
        return "admin"
    if (group_id >= 25):
        return "secretary"
    if (group_id >= 2):
        return "umpire"
    if (group_id >= 1):
        return "user"
    return "unknown"

@router.get("/{username}")
def listUsers(username : str, db: Session = Depends(get_db)):
    rows = db.execute(text('''SELECT u.username, u.email, s.name section, u.group, c.name club
                        FROM user u
                           LEFT JOIN club c ON u.club_id = c.id 
                           LEFT JOIN section s ON u.section_id = s.id
                        WHERE u.username = :username'''), {'username':username})
    
    users = {}
    for row in rows.mappings().all():
        user = { k: v for k,v in dict(row).items() if v }
        user['group'] = group(user.pop('group', 0))
        users[user.pop('username')] = user

    return {"status": "ok", "data": users }

