# Loan Management System

## Project Initialization

This repository contains the **initial setup of the Loan Management System project**.
The repository has been created and the **basic project structure has been organized** to support collaborative development among team members.

So far, the following steps have been completed:

* Created the GitHub repository
* Added team collaborators
* Created the basic project folder structure
* Added the project `README.md`
* Initialized the repository for team development

This structure will help team members organize their work properly and avoid conflicts while developing different parts of the system.

---

## Project Folder Structure

The current project structure is organized as follows:

```
Loan-Management-System
│
├── frontend/
│
├── backend/
│
├── database/
│
└── README.md
```

### Folder Description

**frontend/**
Contains the user interface of the application such as login pages, dashboards, and user interaction components.

**backend/**
Contains the server-side logic, APIs, authentication, and business logic for managing loan operations.

**database/**
Contains database schema, table structures, and queries related to the Loan Management System.

---

## Git Branch Structure

To manage development efficiently among team members, the project follows a **branch-based workflow**.

```
main
 │
 └── develop
      │
      ├── feature-login
      ├── feature-signup
      ├── feature-dashboard
      ├── feature-loan-application
      ├── feature-loan-status
      ├── feature-database
      └── feature-testing
```

### Branch Description

**main**
Contains the stable and final version of the project.

**develop**
This branch is used to integrate all completed features before merging into the main branch.

**feature branches**
Each team member will create their own feature branch to work on specific tasks without affecting others' work.

Example feature branch names:

* feature-login
* feature-signup
* feature-dashboard
* feature-loan-application
* feature-database

---

## Team Development Workflow

The development process will follow these steps:

1. Clone the repository
2. Switch to the develop branch
3. Create a feature branch for the assigned task
4. Implement the feature
5. Commit and push changes
6. Create a Pull Request to merge into the develop branch
7. After review, the feature will be merged

Once all features are completed and tested, the **develop branch will be merged into the main branch**.

---

## Project Goal

The goal of this project is to develop a system that allows:

* Users to apply for loans
* Track loan status
* Manage loan approvals
* Maintain loan records
* Provide administrative control for loan management

---

## Team Collaboration

This project is being developed collaboratively by a team of **7 members using Git and GitHub**.

All team members will follow the **branching and pull request workflow** to maintain an organized and efficient development process.

---

## Status

Project initialization completed.
Development phase will begin using the defined folder and branch structure.
And at last i created a develop branch and run this command git push -u origin develop so if you guys push it will pushed here.
![git command used here and what happend](![alt text](<Screenshot 2026-03-07 192137.png>))

## Final Important Rule
# GitHub Team Workflow – Loan Management System

# Branch Structure

The repository follows this structure:

```
main
 │
 └── develop
        │
        ├── feature-login
        ├── feature-signup
        ├── feature-dashboard
        ├── feature-database
        └── feature-testing
```

## Branch Description

**main**

* Contains the stable and final version of the project.

**develop**

* Integration branch where completed features are merged.

**feature branches**

* Individual team members work on separate feature branches.

---

# Team Member Workflow

Each team member must follow the steps below when starting work.

## Step 1: Switch to develop branch

```
git checkout develop
```

This switches your working branch to **develop**.

---

## Step 2: Pull the latest code

```
git pull origin develop
```

This downloads the latest updates from GitHub so that everyone works with the most recent code.

---

## Step 3: Create a feature branch

Create a new branch for your assigned task.

Example:

```
git checkout -b feature-login
```

Branch naming format:

```
feature-<task-name>
```

Examples:

```
feature-login
feature-signup
feature-dashboard
feature-database
```

---

# After Writing Code

Once you complete your changes, follow these steps.

## Step 4: Check modified files

```
git status
```

This shows which files were changed or added.

---

## Step 5: Add files to staging

To add all changed files:

```
git add .
```

Or add a specific file:

```
git add filename
```

---

## Step 6: Commit the changes

```
git commit -m "Added login page UI"
```

A commit message should clearly describe the work done.

Examples:

```
Added login page UI
Created database schema
Implemented loan application form
```

---

## Step 7: Push the feature branch

Push your branch to GitHub.

```
git push -u origin feature-login
```

This uploads the branch to the GitHub repository.

---

# Creating a Pull Request

After pushing the branch:

1. Go to the GitHub repository.
2. Click **Compare & Pull Request**.
3. Select:

```
Base branch: develop
Compare branch: feature-login
```

4. Write a description of your changes.
5. Click **Create Pull Request**.

---

# Code Review and Merge

The **team leader** will review the Pull Request.

If the code is correct:

* The Pull Request will be merged into the **develop** branch.

Workflow:

```
feature branch
      ↓
Pull Request
      ↓
develop
```

---

# Important Rules

Team members must follow these rules:

❌ Do NOT push directly to `main`

❌ Do NOT push directly to `develop`

✔ Always create a **feature branch**

✔ Always create a **Pull Request**

---

# Complete Workflow Summary

```
git checkout develop
git pull origin develop
git checkout -b feature-login

# write code

git add .
git commit -m "Added login functionality"
git push -u origin feature-login
```

Then create a **Pull Request to the develop branch**.

---

# Final Development Flow

```
feature branch
      ↓
Pull Request
      ↓
develop
      ↓
main (final release)
```

This workflow ensures **organized development, easier collaboration, and fewer merge conflicts**.

