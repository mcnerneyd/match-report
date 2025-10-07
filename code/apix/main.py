from fastapi import FastAPI
import admin, fixtures, users

app = FastAPI(title="Match Report API", version="2.0", root_path="/api/v2")

@app.get("/health")
def health():
    return {"status": "ok"}

app.include_router(fixtures.router)
app.include_router(admin.router)
app.include_router(users.router)
