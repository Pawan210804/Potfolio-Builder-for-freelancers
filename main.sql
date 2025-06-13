-- Table: users (Represents the freelancers themselves)
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()), -- Use VARCHAR(36) and UUID() for MySQL
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    professional_title VARCHAR(255),
    bio TEXT,
    profile_picture_url VARCHAR(500),
    phone_number VARCHAR(50),
    website_url VARCHAR(500),
    linkedin_url VARCHAR(500),
    github_url VARCHAR(500),
    twitter_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- MySQL's TIMESTAMP doesn't need WITH TIME ZONE
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- Auto-update on modification
);

-- Table: user_settings (For UI customization, themes, etc.)
CREATE TABLE user_settings (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) UNIQUE NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    theme_name VARCHAR(100) DEFAULT 'default',
    primary_color VARCHAR(7) DEFAULT '#007bff', -- Hex color code
    secondary_color VARCHAR(7) DEFAULT '#6c757d',
    font_family VARCHAR(100) DEFAULT 'Arial, sans-serif',
    show_contact_form BOOLEAN DEFAULT TRUE,
    custom_css TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: skills
CREATE TABLE skills (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    skill_name VARCHAR(100) UNIQUE NOT NULL
);

-- Junction Table: user_skills (Many-to-many relationship between users and skills)
CREATE TABLE user_skills (
    user_id VARCHAR(36) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    skill_id VARCHAR(36) NOT NULL REFERENCES skills(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, skill_id)
);

-- Table: services (Services offered by freelancers)
CREATE TABLE services (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    service_name VARCHAR(255) NOT NULL,
    description TEXT,
    price_info VARCHAR(255), -- e.g., "$50/hour", "Project-based"
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: projects (Portfolio projects)
CREATE TABLE projects (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    completion_date DATE,
    client_name VARCHAR(255),
    project_url VARCHAR(500), -- Link to live project
    repo_url VARCHAR(500), -- Link to code repository (if applicable)
    is_featured BOOLEAN DEFAULT FALSE, -- To highlight certain projects
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: project_media (Images, videos, documents related to projects)
CREATE TABLE project_media (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    project_id VARCHAR(36) NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    media_url VARCHAR(500) NOT NULL,
    media_type VARCHAR(50) NOT NULL, -- e.g., 'image', 'video', 'document'
    caption TEXT,
    display_order INT DEFAULT 0, -- To control order of media in project view
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: project_technologies (Technologies used in projects - Many-to-many with a 'technologies' table similar to 'skills')
CREATE TABLE technologies (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    tech_name VARCHAR(100) UNIQUE NOT NULL
);

CREATE TABLE project_technologies (
    project_id VARCHAR(36) NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    technology_id VARCHAR(36) NOT NULL REFERENCES technologies(id) ON DELETE CASCADE,
    PRIMARY KEY (project_id, technology_id)
);

-- Table: testimonials
CREATE TABLE testimonials (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    user_id VARCHAR(36) NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- The freelancer receiving the testimonial
    client_name VARCHAR(255) NOT NULL,
    client_title VARCHAR(255), -- e.g., "CEO of ABC Corp"
    client_company VARCHAR(255),
    testimonial_text TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5), -- Optional 1-5 star rating
    testimonial_date DATE,
    is_approved BOOLEAN DEFAULT FALSE, -- For moderation
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: contact_messages (If you implement an internal contact form)
CREATE TABLE contact_messages (
    id VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    freelancer_id VARCHAR(36) NOT NULL REFERENCES users(id) ON DELETE CASCADE, -- The freelancer being contacted
    sender_name VARCHAR(255) NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE
);
