<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carmilito Aboda Jr. | Futuristic Portfolio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vanilla-tilt@1.7.0/dist/vanilla-tilt.min.js"></script>
    <style>
        :root {
            --neon-blue: #0ff0fc;
            --neon-pink: #ff2ced;
            --neon-purple: #9600ff;
            --dark-space: #0a0a1a;
            --star-light: rgba(255, 255, 255, 0.8);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Orbitron', 'Montserrat', sans-serif;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        @keyframes neon-glow {
            0%, 100% { text-shadow: 0 0 10px var(--neon-blue), 0 0 20px var(--neon-blue), 0 0 30px var(--neon-purple); }
            50% { text-shadow: 0 0 5px var(--neon-blue), 0 0 15px var(--neon-pink), 0 0 25px var(--neon-purple); }
        }
        
        body {
            background-color: var(--dark-space);
            color: white;
            overflow-x: hidden;
            position: relative;
        }
        
        /* 3D Background Canvas */
        #spaceCanvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.8;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Header */
        header {
            padding: 30px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(10px);
            background: rgba(10, 10, 26, 0.7);
            border-bottom: 1px solid rgba(150, 0, 255, 0.3);
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(to right, var(--neon-blue), var(--neon-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: neon-glow 3s infinite alternate;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            position: relative;
            padding: 5px 0;
            transition: all 0.3s;
        }
        
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--neon-blue);
            transition: width 0.3s;
        }
        
        .nav-links a:hover {
            color: var(--neon-blue);
        }
        
        .nav-links a:hover::after {
            width: 100%;
        }
        
        /* Hero Section */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding-top: 100px;
        }
        
        .hero-content {
            width: 50%;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 4.5rem;
            line-height: 1.1;
            margin-bottom: 20px;
            background: linear-gradient(to right, var(--neon-blue), var(--neon-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: neon-glow 3s infinite alternate;
        }
        
        .hero p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 80%;
            line-height: 1.8;
        }
        
        .btn {
            display: inline-block;
            padding: 15px 35px;
            background: transparent;
            color: white;
            border: 2px solid var(--neon-blue);
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            z-index: 1;
            font-size: 1.1rem;
            margin-right: 20px;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(to right, var(--neon-blue), var(--neon-purple));
            transition: width 0.3s;
            z-index: -1;
        }
        
        .btn:hover {
            color: var(--dark-space);
            box-shadow: 0 0 20px rgba(0, 255, 252, 0.5);
        }
        
        .btn:hover::before {
            width: 100%;
        }
        
        .btn-primary {
            border-color: var(--neon-blue);
        }
        
        .btn-secondary {
            border-color: var(--neon-pink);
        }
        
        .btn-secondary::before {
            background: linear-gradient(to right, var(--neon-pink), var(--neon-purple));
        }
        
        .hero-image {
            position: absolute;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 50%;
            height: 80%;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .holographic-card {
            width: 400px;
            height: 500px;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            border: 1px solid rgba(150, 0, 255, 0.3);
            box-shadow: 0 0 30px rgba(150, 0, 255, 0.3),
                        inset 0 0 20px rgba(150, 0, 255, 0.2);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .holographic-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                transparent,
                transparent,
                transparent,
                var(--neon-blue),
                transparent,
                transparent,
                var(--neon-pink)
            );
            transform: rotate(30deg);
            animation: pulse 6s linear infinite;
            opacity: 0.6;
        }
        
        .profile-3d {
            width: 90%;
            height: 90%;
            border-radius: 15px;
            object-fit: cover;
            position: relative;
            z-index: 2;
            filter: contrast(110%) brightness(90%) saturate(110%);
            border: 1px solid rgba(150, 0, 255, 0.5);
        }
        
        /* About Section */
        .about {
            padding: 150px 0;
            position: relative;
        }
        
        .section-title {
            font-size: 3rem;
            text-align: center;
            margin-bottom: 80px;
            position: relative;
            display: inline-block;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(to right, var(--neon-blue), var(--neon-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: neon-glow 3s infinite alternate;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--neon-blue), var(--neon-pink), transparent);
        }
        
        .about-content {
            display: flex;
            gap: 50px;
            align-items: center;
        }
        
        .about-text {
            flex: 1;
        }
        
        .about-text h3 {
            font-size: 1.8rem;
            margin-bottom: 30px;
            color: var(--neon-blue);
        }
        
        .about-text p {
            margin-bottom: 20px;
            line-height: 1.8;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .tech-stack {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        .tech-item {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(5px);
            padding: 25px 15px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(150, 0, 255, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .tech-item:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 255, 252, 0.2);
            border-color: var(--neon-blue);
        }
        
        .tech-item i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(to bottom, var(--neon-blue), var(--neon-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .tech-item h4 {
            margin-bottom: 10px;
            font-size: 1.2rem;
        }
        
        /* Projects Section */
        .projects {
            padding: 150px 0;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 50px;
        }
        
        .project-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.5s;
            position: relative;
            border: 1px solid rgba(150, 0, 255, 0.2);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .project-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(0, 255, 252, 0.1),
                transparent
            );
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .project-card:hover::before {
            transform: translateX(100%);
        }
        
        .project-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 40px rgba(0, 255, 252, 0.3);
            border-color: var(--neon-blue);
        }
        
        .project-img-container {
            height: 250px;
            overflow: hidden;
        }
        
        .project-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .project-card:hover .project-img {
            transform: scale(1.1);
        }
        
        .project-info {
            padding: 25px;
        }
        
        .project-info h3 {
            margin-bottom: 15px;
            font-size: 1.5rem;
            color: var(--neon-blue);
        }
        
        .project-info p {
            margin-bottom: 20px;
            opacity: 0.9;
            line-height: 1.7;
        }
        
        .project-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tag {
            background: rgba(0, 255, 252, 0.1);
            color: var(--neon-blue);
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            border: 1px solid var(--neon-blue);
        }
        
        /* Contact Section */
        .contact {
            padding: 150px 0;
            text-align: center;
        }
        
        .contact-form {
            max-width: 700px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            padding: 50px;
            border-radius: 20px;
            margin-top: 50px;
            border: 1px solid rgba(150, 0, 255, 0.3);
            box-shadow: 0 0 30px rgba(150, 0, 255, 0.2);
        }
        
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
            color: var(--neon-blue);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(150, 0, 255, 0.3);
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(0, 255, 252, 0.3);
        }
        
        .form-group textarea {
            height: 150px;
            resize: none;
        }
        
        /* Footer */
        footer {
            padding: 50px 0;
            text-align: center;
            background: rgba(0, 0, 0, 0.3);
            margin-top: 100px;
            border-top: 1px solid rgba(150, 0, 255, 0.3);
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .social-links a {
            color: white;
            font-size: 1.8rem;
            transition: all 0.3s;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(150, 0, 255, 0.3);
        }
        
        .social-links a:hover {
            color: var(--neon-blue);
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 5px 15px rgba(0, 255, 252, 0.3);
            border-color: var(--neon-blue);
        }
        
        .copyright {
            opacity: 0.7;
            font-size: 0.9rem;
        }
        
        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .hero-content {
                width: 60%;
            }
            
            .hero-image {
                width: 40%;
            }
        }
        
        @media (max-width: 992px) {
            .hero-content {
                width: 100%;
                text-align: center;
            }
            
            .hero h1 {
                font-size: 3.5rem;
            }
            
            .hero p {
                max-width: 100%;
                margin-left: auto;
                margin-right: auto;
            }
            
            .hero-image {
                display: none;
            }
            
            .about-content {
                flex-direction: column;
            }
            
            .tech-stack {
                margin-top: 50px;
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .hero h1 {
                font-size: 2.8rem;
            }
            
            .section-title {
                font-size: 2.5rem;
            }
            
            .projects-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-form {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- 3D Space Background -->
    <canvas id="spaceCanvas"></canvas>
    
    <!-- Floating Particles -->
    <div class="particles" id="particles-js"></div>
    
    <!-- Header -->
    <header>
        <div class="container">
            <nav>
                <div class="logo">CAJ</div>
                <div class="nav-links">
                    <a href="#home">Home</a>
                    <a href="#about">About</a>
                    <a href="#projects">Projects</a>
                    <a href="#contact">Contact</a>
                </div>
            </nav>
        </div>
    </header>
    
    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h1>Carmilito Aboda Jr.</h1>
                <p>Visionary developer and designer creating immersive digital experiences at the intersection of technology and art. Specializing in cutting-edge web solutions with a futuristic aesthetic.</p>
                <div>
                    <a href="#projects" class="btn btn-primary">View Work</a>
                    <a href="#contact" class="btn btn-secondary">Contact Me</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="holographic-card" data-tilt data-tilt-scale="1.05" data-tilt-glare data-tilt-max-glare="0.3">
                    <!-- Replace with your actual image -->
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=774&q=80" alt="Carmilito Aboda Jr." class="profile-3d">
                </div>
            </div>
        </div>
    </section>
    
    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <h2 class="section-title">About Me</h2>
            <div class="about-content">
                <div class="about-text">
                    <h3>Pushing Boundaries in Digital Creation</h3>
                    <p>As a forward-thinking developer and designer, I specialize in creating experiences that blend technical excellence with artistic vision. With 7+ years in the industry, I've helped startups and enterprises transform their digital presence.</p>
                    <p>My approach combines deep technical knowledge with a passion for innovative design. I believe the best digital products emerge when functionality and aesthetics work in harmony.</p>
                    <p>When not coding or designing, I contribute to open-source projects, speak at tech conferences, and explore emerging technologies like Web3 and AI integration.</p>
                </div>
                <div class="tech-stack">
                    <div class="tech-item">
                        <i class="fab fa-react"></i>
                        <h4>React</h4>
                        <p>Advanced SPAs</p>
                    </div>
                    <div class="tech-item">
                        <i class="fab fa-node-js"></i>
                        <h4>Node.js</h4>
                        <p>Backend Systems</p>
                    </div>
                    <div class="tech-item">
                        <i class="fas fa-cube"></i>
                        <h4>Three.js</h4>
                        <p>3D Experiences</p>
                    </div>
                    <div class="tech-item">
                        <i class="fab fa-figma"></i>
                        <h4>Figma</h4>
                        <p>UI/UX Design</p>
                    </div>
                    <div class="tech-item">
                        <i class="fas fa-robot"></i>
                        <h4>AI Integration</h4>
                        <p>Smart Solutions</p>
                    </div>
                    <div class="tech-item">
                        <i class="fab fa-ethereum"></i>
                        <h4>Web3</h4>
                        <p>Blockchain Apps</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Projects Section -->
    <section class="projects" id="projects">
        <div class="container">
            <h2 class="section-title">Featured Projects</h2>
            <div class="projects-grid">
                <div class="project-card" data-tilt data-tilt-glare data-tilt-max-glare="0.2">
                    <div class="project-img-container">
                        <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=870&q=80" alt="Project 1" class="project-img">
                    </div>
                    <div class="project-info">
                        <h3>Quantum Dashboard</h3>
                        <p>Next-gen analytics platform with 3D data visualization and AI-powered insights for enterprise clients.</p>
                        <div class="project-tags">
                            <span class="tag">React</span>
                            <span class="tag">Three.js</span>
                            <span class="tag">TensorFlow</span>
                        </div>
                        <a href="#" class="btn btn-primary">Explore Project</a>
                    </div>
                </div>
                <div class="project-card" data-tilt data-tilt-glare data-tilt-max-glare="0.2">
                    <div class="project-img-container">
                        <img src="https://images.unsplash.com/photo-1467232004584-a241de8bcf5d?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=869&q=80" alt="Project 2" class="project-img">
                    </div>
                    <div class="project-info">
                        <h3>NeuroFitness AR</h3>
                        <p>Augmented reality fitness app that adapts workouts in real-time using biometric feedback.</p>
                        <div class="project-tags">
                            <span class="tag">ARKit</span>
                            <span class="tag">Swift</span>
                            <span class="tag">HealthKit</span>
                        </div>
                        <a href="#" class="btn btn-primary">Explore Project</a>
                    </div>
                </div>
                <div class="project-card" data-tilt data-tilt-glare data-tilt-max-glare="0.2">
                    <div class="project-img-container">
                        <img src="https://images.unsplash.com/photo-1555774698-0b77e0d5fac6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=870&q=80" alt="Project 3" class="project-img">
                    </div>
                    <div class="project-info">
                        <h3>CryptoVerse</h3>
                        <p>Immersive Web3 platform for NFT galleries and virtual blockchain experiences.</p>
                        <div class="project-tags">
                            <span class="tag">Web3.js</span>
                            <span class="tag">Three.js</span>
                            <span class="tag">Solidity</span>
                        </div>
                        <a href="#" class="btn btn-primary">Explore Project</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <h2 class="section-title">Get In Touch</h2>
            <div class="contact-form" data-tilt data-tilt-glare data-tilt-max-glare="0.1">
                <form>
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="social-links">
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-github"></i></a>
                <a href="#"><i class="fab fa-dribbble"></i></a>
                <a href="#"><i class="fab fa-behance"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
            </div>
            <p class="copyright">© 2023 Carmilito Aboda Jr. | All rights reserved</p>
        </div>
    </footer>

    <script>
        // Initialize 3D Space Background
        function initSpaceBackground() {
            const canvas = document.getElementById('spaceCanvas');
            const renderer = new THREE.WebGLRenderer({ canvas, antialias: true });
            renderer.setSize(window.innerWidth, window.innerHeight);
            
            const scene = new THREE.Scene();
            const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            camera.position.z = 30;
            
            // Create stars
            const starsGeometry = new THREE.BufferGeometry();
            const starCount = 5000;
            
            const positions = new Float32Array(starCount * 3);
            const sizes = new Float32Array(starCount);
            const colors = new Float32Array(starCount * 3);
            
            for (let i = 0; i < starCount; i++) {
                const i3 = i * 3;
                
                // Random positions in a sphere
                positions[i3] = (Math.random() - 0.5) * 2000;
                positions[i3 + 1] = (Math.random() - 0.5) * 2000;
                positions[i3 + 2] = (Math.random() - 0.5) * 2000;
                
                // Random sizes
                sizes[i] = Math.random() * 2;
                
                // Random colors (mostly white with some blue/purple)
                colors[i3] = 0.9 + Math.random() * 0.1;
                colors[i3 + 1] = 0.8 + Math.random() * 0.2;
                colors[i3 + 2] = 0.9 + Math.random() * 0.1;
            }
            
            starsGeometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));
            starsGeometry.setAttribute('color', new THREE.BufferAttribute(colors, 3));
            starsGeometry.setAttribute('size', new THREE.BufferAttribute(sizes, 1));
            
            const starsMaterial = new THREE.PointsMaterial({
                size: 1,
                vertexColors: true,
                transparent: true,
                opacity: 0.8,
                sizeAttenuation: true
            });
            
            const stars = new THREE.Points(starsGeometry, starsMaterial);
            scene.add(stars);
            
            // Animation loop
            function animate() {
                requestAnimationFrame(animate);
                
                stars.rotation.x += 0.0001;
                stars.rotation.y += 0.0001;
                
                renderer.render(scene, camera);
            }
            
            animate();
            
            // Handle window resize
            window.addEventListener('resize', () => {
                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);
            });
        }
        
        // Initialize floating particles
        function initParticles() {
            const particlesCount = 100;
            const particles = [];
            
            for (let i = 0; i < particlesCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random properties
                const size = Math.random() * 5 + 2;
                const posX = Math.random() * window.innerWidth;
                const posY = Math.random() * window.innerHeight;
                const delay = Math.random() * 5;
                const duration = Math.random() * 10 + 10;
                const opacity = Math.random() * 0.5 + 0.1;
                
                // Apply styles
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}px`;
                particle.style.top = `${posY}px`;
                particle.style.opacity = opacity;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                
                // Add to DOM
                document.getElementById('particles-js').appendChild(particle);
                particles.push(particle);
            }
        }
        
        // Initialize tilt effects
        function initTiltEffects() {
            VanillaTilt.init(document.querySelectorAll(".holographic-card, .project-card, .contact-form"), {
                max: 15,
                speed: 400,
                glare: true,
                "max-glare": 0.3,
                scale: 1.03
            });
        }
        
        // Initialize everything when DOM loads
        document.addEventListener('DOMContentLoaded', () => {
            initSpaceBackground();
            initParticles();
            initTiltEffects();
        });
    </script>
</body>
</html>