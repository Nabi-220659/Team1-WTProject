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
