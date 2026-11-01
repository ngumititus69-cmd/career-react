import { createClient } from "@supabase/supabase-js";
import { useCallback, useEffect, useMemo, useState } from "react";

const supabaseUrl = import.meta.env.VITE_SUPABASE_URL;
const supabaseAnonKey = import.meta.env.VITE_SUPABASE_ANON_KEY;
const supabase =
  supabaseUrl && supabaseAnonKey ? createClient(supabaseUrl, supabaseAnonKey) : null;

const careerStages = [
  ["secondary_student", "Secondary school student"],
  ["college_student", "College student"],
  ["university_student", "University student"],
  ["graduate", "Graduate"],
  ["working_professional", "Working professional"],
  ["career_changer", "Exploring a career change"],
];

const questions = [
  {
    key: "interests",
    label: "Your interests",
    title: "What do you enjoy doing?",
    description: "Select everything that genuinely interests you.",
    options: [
      "Solving problems", "Building technology", "Working with data", "Helping people",
      "Creating visual designs", "Starting projects", "Researching ideas",
      "Writing and communication", "Improving processes", "Creating things",
      "Working outdoors", "Share knowledge",
    ],
  },
  {
    key: "skills",
    label: "Your skills",
    title: "What skills do you have or want to build?",
    description: "Include skills you are still developing. Growth counts.",
    options: [
      "Programming", "Problem solving", "Communication", "Analytical thinking",
      "Visual design", "Data analysis", "Content creation", "Organisation",
      "Research", "Teamwork",
    ],
  },
  {
    key: "subjects",
    label: "Subjects you enjoy",
    title: "Which subjects appeal to you?",
    description: "Choose subjects you have enjoyed or would like to explore.",
    options: [
      "Mathematics", "Computer Studies", "Physics", "Business Studies", "English",
      "Art and Design", "Biology", "Chemistry", "Geography",
    ],
  },
  {
    key: "work_styles",
    label: "Your work preferences",
    title: "What kind of work feels right to you?",
    description: "Choose environments where you think you could do your best.",
    options: [
      "Independent focus", "Collaborative teamwork", "Flexible schedule",
      "Structured work", "Hands-on practice", "Remote or hybrid",
    ],
  },
  {
    key: "goals",
    label: "Your ambitions",
    title: "What would you like your work to help you do?",
    description: "Pick the ambitions that matter most to you right now.",
    options: [
      "Build digital products", "Work with data", "Help people directly",
      "Grow a business", "Start a business", "Solve community problems",
      "Share knowledge",
    ],
  },
];

const jobBoards = [
  {
    title: "Fuzu Kenya",
    description: "Explore jobs and career opportunities on Fuzu's Kenya website.",
    label: "JOB SEARCH PLATFORM",
    url: "https://www.fuzu.com/kenya",
    domain: "fuzu.com/kenya",
    icon: "bi-globe2",
    tone: "blue",
    action: "Visit Fuzu Kenya",
  },
  {
    title: "Corporate Staffing",
    description: "Browse vacancies published by Corporate Staffing Services in Kenya.",
    label: "RECRUITMENT & JOBS",
    url: "https://www.corporatestaffing.co.ke/jobs/",
    domain: "corporatestaffing.co.ke/jobs",
    icon: "bi-briefcase",
    tone: "violet",
    action: "Browse vacancies",
  },
  {
    title: "MyJobsInKenya",
    description: "Search job opportunities listed on the MyJobsInKenya website.",
    label: "KENYAN JOB LISTINGS",
    url: "https://www.myjobsinkenya.com/",
    domain: "myjobsinkenya.com",
    icon: "bi-search",
    tone: "green",
    action: "Visit MyJobsInKenya",
  },
];

const iconByCategory = {
  Technology: "bi-code-slash",
  "Creative work": "bi-palette2",
  Business: "bi-briefcase",
  Education: "bi-mortarboard",
  Entrepreneurship: "bi-lightbulb",
  Agriculture: "bi-flower1",
  Healthcare: "bi-heart-pulse",
};

function routeFromLocation() {
  const search = new URLSearchParams(window.location.search);
  return {
    page: search.get("page") || "home",
    slug: search.get("slug") || "",
    careerId: search.get("career") || "",
    search: search.get("search") || "",
    category: search.get("category") || "",
  };
}

function Brand({ onNavigate, footer = false }) {
  return (
    <a className={`brand${footer ? " brand-footer" : ""}`} href="?page=home" onClick={(event) => {
      event.preventDefault();
      onNavigate({ page: "home" });
    }}>
      <span className="brand-mark"><i className="bi bi-signpost-2-fill" /></span>
      <span>careers <span className="brand-accent">react app</span><small>FIND YOUR NEXT DIRECTION</small></span>
    </a>
  );
}

function Eyebrow({ children, light = false }) {
  return <span className={`eyebrow${light ? " eyebrow-light" : ""}`}><i />{children}</span>;
}

function Button({ children, secondary = false, ...props }) {
  return <button className={`button${secondary ? " button-secondary" : " button-primary"}`} {...props}>{children}</button>;
}

function LinkButton({ children, secondary = false, href, onClick }) {
  return <a className={`button${secondary ? " button-secondary" : " button-primary"}`} href={href} onClick={onClick}>{children}</a>;
}

function Alert({ message, type = "error", onDismiss }) {
  if (!message) return null;
  return <div className={`alert alert-${type}`} role={type === "error" ? "alert" : "status"}><span>{message}</span>{onDismiss && <button type="button" aria-label="Dismiss message" onClick={onDismiss}><i className="bi bi-x-lg" /></button>}</div>;
}

function App() {
  const [route, setRoute] = useState(routeFromLocation);
  const [session, setSession] = useState(null);
  const [authReady, setAuthReady] = useState(!supabase);
  const [message, setMessage] = useState("");
  const [messageType, setMessageType] = useState("error");
  const [menuOpen, setMenuOpen] = useState(false);
  const [busy, setBusy] = useState(false);
  const [data, setData] = useState(null);

  const navigate = useCallback((next, replace = false) => {
    const params = new URLSearchParams();
    params.set("page", next.page || "home");
    for (const key of ["slug", "career", "search", "category"]) {
      if (next[key]) params.set(key, next[key]);
    }
    const target = `${window.location.pathname}?${params.toString()}`;
    window.history[replace ? "replaceState" : "pushState"]({}, "", target);
    setRoute(routeFromLocation());
    setData(null);
    setMessage("");
    setMenuOpen(false);
    window.scrollTo({ top: 0, behavior: "smooth" });
  }, []);

  useEffect(() => {
    const onPopState = () => {
      setRoute(routeFromLocation());
      setData(null);
      setMessage("");
    };
    window.addEventListener("popstate", onPopState);
    return () => window.removeEventListener("popstate", onPopState);
  }, []);

  useEffect(() => {
    if (!supabase) return undefined;
    let active = true;
    supabase.auth.getSession().then(({ data: result, error }) => {
      if (!active) return;
      if (error) setMessage(error.message);
      setSession(result?.session ?? null);
      setAuthReady(true);
    });
    const { data: listener } = supabase.auth.onAuthStateChange((_event, nextSession) => {
      setSession(nextSession);
      setAuthReady(true);
    });
    return () => {
      active = false;
      listener.subscription.unsubscribe();
    };
  }, []);

  const api = useCallback(async (action, { method = "GET", body, params = {} } = {}) => {
    const query = new URLSearchParams({ action, ...params });
    const headers = { Accept: "application/json" };
    if (session?.access_token) headers.Authorization = `Bearer ${session.access_token}`;
    if (body !== undefined) headers["Content-Type"] = "application/json";
    const response = await fetch(`/api?${query}`, {
      method,
      headers,
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || "The request could not be completed.");
    return result;
  }, [session]);

  useEffect(() => {
    if (!authReady) return undefined;
    let active = true;
    async function loadPage() {
      const needsAuth = ["dashboard", "assessment", "results", "saved", "roadmap"].includes(route.page);
      if (needsAuth && !session) {
        navigate({ page: "login" }, true);
        setMessage("Sign in to continue to your career journey.");
        setMessageType("error");
        return;
      }

      try {
        let result;
        if (route.page === "explore") {
          result = await api("careers", {
            params: { search: route.search, category: route.category },
          });
        } else if (route.page === "career") {
          result = await api("career", { params: { slug: route.slug } });
        } else if (route.page === "dashboard" || route.page === "results") {
          result = await api("dashboard");
        } else if (route.page === "saved") {
          result = await api("saved");
        } else if (route.page === "roadmap") {
          result = await api("progress", { params: { career_id: route.careerId } });
        }
        if (active) setData(result ?? null);
      } catch (error) {
        if (active) {
          setData(null);
          setMessage(error.message);
          setMessageType("error");
        }
      }
    }
    loadPage();
    return () => { active = false; };
  }, [api, authReady, navigate, route, session]);

  const signOut = async () => {
    if (!supabase) return;
    setBusy(true);
    const { error } = await supabase.auth.signOut();
    setBusy(false);
    if (error) {
      setMessage(error.message);
      setMessageType("error");
      return;
    }
    navigate({ page: "home" });
    setMessage("You have signed out.");
    setMessageType("success");
  };

  const changeMessage = (text, type = "error") => {
    setMessage(text);
    setMessageType(type);
  };

  const pageTitles = {
    home: "Find your direction",
    register: "Create your account",
    login: "Welcome back",
    dashboard: "Your dashboard",
    assessment: "Career discovery assessment",
    results: "Your career matches",
    explore: "Explore careers",
    jobs: "Job vacancies in Kenya",
    career: "Career profile",
    roadmap: "Your career roadmap",
    saved: "Saved careers",
  };
  useEffect(() => {
    document.title = `${pageTitles[route.page] || "Careers React App"} | Careers React App`;
  }, [route.page]);

  const page = useMemo(() => {
    if (!authReady) return <LoadingState text="Opening your career compass..." />;
    switch (route.page) {
      case "register":
      case "login":
        return <AuthPage mode={route.page} busy={busy} setBusy={setBusy} message={message} setMessage={changeMessage} onNavigate={navigate} />;
      case "dashboard":
        return <Dashboard data={data} onNavigate={navigate} api={api} setMessage={changeMessage} />;
      case "assessment":
        return <Assessment api={api} setBusy={setBusy} busy={busy} onNavigate={navigate} setMessage={changeMessage} />;
      case "results":
        return <Results data={data} onNavigate={navigate} api={api} setMessage={changeMessage} />;
      case "explore":
        return <Explore data={data} route={route} onNavigate={navigate} api={api} setMessage={changeMessage} />;
      case "jobs":
        return <JobsPage onNavigate={navigate} />;
      case "career":
        return <CareerProfile career={data?.career} loading={!data && !message} onNavigate={navigate} api={api} setMessage={changeMessage} session={session} />;
      case "saved":
        return <SavedCareers data={data} onNavigate={navigate} api={api} setMessage={changeMessage} />;
      case "roadmap":
        return <Roadmap data={data} loading={!data && !message} api={api} setMessage={changeMessage} />;
      default:
        return <Home onNavigate={navigate} />;
    }
  }, [api, authReady, busy, data, message, navigate, route, session]);

  return (
    <div className="app-shell">
      <header className="site-header">
        <div className="nav-wrap">
          <Brand onNavigate={navigate} />
          <button className="mobile-toggle" type="button" aria-label="Toggle navigation" aria-expanded={menuOpen} onClick={() => setMenuOpen(!menuOpen)}><i className={`bi ${menuOpen ? "bi-x-lg" : "bi-list"}`} /></button>
          <nav className={`main-nav${menuOpen ? " is-open" : ""}`} aria-label="Main navigation">
            <a href="?page=explore" onClick={(event) => { event.preventDefault(); navigate({ page: "explore" }); }}>Explore careers</a>
            <a href="?page=jobs" onClick={(event) => { event.preventDefault(); navigate({ page: "jobs" }); }}>Job vacancies</a>
            {session ? <>
              <a href="?page=dashboard" onClick={(event) => { event.preventDefault(); navigate({ page: "dashboard" }); }}>My dashboard</a>
              <button className="nav-button" type="button" disabled={busy} onClick={signOut}>Sign out</button>
            </> : <>
              <a href="?page=login" onClick={(event) => { event.preventDefault(); navigate({ page: "login" }); }}>Sign in</a>
              <LinkButton href="?page=register" onClick={(event) => { event.preventDefault(); navigate({ page: "register" }); }}>Get started <i className="bi bi-arrow-right" /></LinkButton>
            </>}
          </nav>
        </div>
      </header>
      <main className="page-main">
        <Alert message={message} type={messageType} onDismiss={() => setMessage("")} />
        {!supabase && <div className="setup-notice"><i className="bi bi-gear" /><span>Netlify setup needed: configure <code>VITE_SUPABASE_URL</code>, <code>VITE_SUPABASE_ANON_KEY</code>, and the private Supabase function variables to enable accounts and saved data.</span></div>}
        {page}
      </main>
      <footer className="site-footer"><div className="footer-inner"><Brand onNavigate={navigate} footer /><p>Discover Your Interests. Identify Your Potential. Build Your Future.</p><span>Starting in Kenya. Open to the world.</span></div></footer>
    </div>
  );
}

function Home({ onNavigate }) {
  return <>
    <section className="hero-section">
      <div className="hero-inner">
        <div className="hero-copy">
          <Eyebrow>A clearer path starts with you</Eyebrow>
          <h1>Your interests<br />could be your <span>future.</span></h1>
          <p>Discover careers that connect your interests, hobbies, education and abilities. Get personalised career matches and practical next steps for your future.</p>
          <div className="hero-actions"><LinkButton href="?page=register" onClick={(event) => { event.preventDefault(); onNavigate({ page: "register" }); }}>Discover my career <i className="bi bi-arrow-up-right" /></LinkButton><LinkButton secondary href="?page=explore" onClick={(event) => { event.preventDefault(); onNavigate({ page: "explore" }); }}><i className="bi bi-compass" /> Explore careers</LinkButton></div>
          <div className="hero-note"><i className="bi bi-stars" /><span><strong>More than a job title.</strong><br />Guidance that starts with your story.</span></div>
        </div>
        <div className="hero-art" aria-label="Career compass illustration">
          <div className="orbit orbit-one" /><div className="orbit orbit-two" />
          <div className="compass"><i className="bi bi-compass-fill" /></div>
          <span className="float-card float-interest"><i className="bi bi-lightbulb" /> Your interests</span>
          <span className="float-card float-potential"><i className="bi bi-graph-up-arrow" /> Your potential</span>
          <span className="float-card float-next"><i className="bi bi-signpost-split" /> Your next step</span>
          <span className="art-caption">YOUR FUTURE ISN'T ONE-SIZE-FITS-ALL</span>
        </div>
      </div>
      <div className="hero-bottom"><span>INTERESTS</span><i className="bi bi-plus-lg" /><span>STRENGTHS</span><i className="bi bi-plus-lg" /><span>AMBITION</span><i className="bi bi-arrow-right" /><strong>A DIRECTION THAT FEELS LIKE YOURS</strong></div>
    </section>
    <section className="section-wrap">
      <div className="section-heading"><Eyebrow>A little clarity goes a long way</Eyebrow><h2>Find your direction,<br /><span>one step at a time.</span></h2><p>Your future doesn't have to be a mystery. Start with what makes you, you.</p></div>
      <div className="steps-grid">{[
        ["bi-person-lines-fill", "Tell us about you", "Share what interests you, what you're good at and what matters in your future."],
        ["bi-stars", "Explore your matches", "See career directions connected to your answers, and understand why they fit."],
        ["bi-signpost-split", "Make a plan", "Turn inspiration into practical next steps for skills, learning and experience."],
      ].map(([icon, title, copy], index) => <article className="step-card" key={title}><span className="step-number">0{index + 1}</span><span className={`step-icon tone-${index}`}><i className={`bi ${icon}`} /></span><h3>{title}</h3><p>{copy}</p></article>)}</div>
    </section>
    <section className="section-wrap career-areas">
      <div className="section-heading"><Eyebrow>The world of work is wide</Eyebrow><h2>There is more than<br /><span>one way forward.</span></h2><p>Explore career directions that connect with what you care about.</p></div>
      <div className="area-grid">{["Technology", "Creative work", "Business", "Education", "Entrepreneurship", "Agriculture", "Healthcare"].map((category) => <button key={category} className="area-card" type="button" onClick={() => onNavigate({ page: "explore", category })}><i className={`bi ${iconByCategory[category] || "bi-compass"}`} />{category}<i className="bi bi-arrow-up-right area-arrow" /></button>)}</div>
      <button className="text-button" type="button" onClick={() => onNavigate({ page: "explore" })}>Explore the career library <i className="bi bi-arrow-right" /></button>
    </section>
    <section className="section-wrap jobs-promo"><div><Eyebrow>Ready to explore opportunities?</Eyebrow><h2>Find current vacancies<br /><span>across Kenya.</span></h2><p>Visit Kenyan job boards to search vacancies published and updated by each employer or recruitment site.</p></div><LinkButton href="?page=jobs" onClick={(event) => { event.preventDefault(); onNavigate({ page: "jobs" }); }}>Explore job websites <i className="bi bi-arrow-up-right" /></LinkButton></section>
    <section className="cta-panel"><Eyebrow light>Your first step starts here</Eyebrow><h2>Curious about where<br />you could go?</h2><p>Create your account, take the discovery assessment and start building your own career roadmap.</p><LinkButton href="?page=register" onClick={(event) => { event.preventDefault(); onNavigate({ page: "register" }); }}>Start your journey <i className="bi bi-arrow-right" /></LinkButton><i className="bi bi-signpost-split cta-watermark" /></section>
  </>;
}

function AuthPage({ mode, busy, setBusy, setMessage, onNavigate }) {
  const isRegister = mode === "register";
  const [form, setForm] = useState({ first_name: "", last_name: "", email: "", career_stage: "", password: "", confirmation: "" });
  const [localError, setLocalError] = useState("");

  const update = (event) => setForm((current) => ({ ...current, [event.target.name]: event.target.value }));
  const submit = async (event) => {
    event.preventDefault();
    setLocalError("");
    setMessage("");
    if (!supabase) {
      setLocalError("Connect Supabase to enable secure registration and sign-in.");
      return;
    }
    if (isRegister && form.password !== form.confirmation) {
      setLocalError("Your passwords do not match.");
      return;
    }
    if (form.password.length < 8) {
      setLocalError("Use a password with at least 8 characters.");
      return;
    }
    setBusy(true);
    const result = isRegister
      ? await supabase.auth.signUp({
        email: form.email.trim(),
        password: form.password,
        options: {
          data: { first_name: form.first_name.trim(), last_name: form.last_name.trim(), career_stage: form.career_stage },
        },
      })
      : await supabase.auth.signInWithPassword({ email: form.email.trim(), password: form.password });
    setBusy(false);
    if (result.error) {
      setLocalError(result.error.message);
      return;
    }
    if (isRegister && !result.data.session) {
      setMessage("Account created. Check your email to confirm the account, then sign in.", "success");
      onNavigate({ page: "login" });
      return;
    }
    setMessage(isRegister ? "Your account is ready. Let’s discover your career direction." : "Welcome back to your career journey.", "success");
    onNavigate({ page: isRegister ? "assessment" : "dashboard" });
  };

  return <section className="auth-layout">
    <div className="auth-intro"><Eyebrow>Your next chapter</Eyebrow><h1>{isRegister ? "Your story is a great place to start." : "Welcome back to your journey."}</h1><p>{isRegister ? "Create your account to save your career discovery answers, personalised matches and roadmap progress." : "Sign in to pick up where you left off and see what your answers revealed."}</p><div className="auth-points"><span><i className="bi bi-check-circle" /> Your own career discovery profile</span><span><i className="bi bi-check-circle" /> A practical, personalised roadmap</span><span><i className="bi bi-check-circle" /> Your progress saved as you go</span></div></div>
    <form className="form-card" onSubmit={submit}>
      <span className="form-symbol"><i className="bi bi-person-lock" /></span><h2>{isRegister ? "Create your account" : "Sign in"}</h2><p>{isRegister ? "It takes just a moment to get started." : "Enter the details you used to register."}</p>
      {localError && <Alert message={localError} />}
      {isRegister && <div className="form-row"><label>First name<input name="first_name" autoComplete="given-name" maxLength="80" required value={form.first_name} onChange={update} /></label><label>Last name<input name="last_name" autoComplete="family-name" maxLength="80" required value={form.last_name} onChange={update} /></label></div>}
      <label>Email address<input name="email" type="email" autoComplete="email" maxLength="254" required value={form.email} onChange={update} /></label>
      {isRegister && <label>Where are you in your career journey?<select name="career_stage" required value={form.career_stage} onChange={update}><option value="">Choose one</option>{careerStages.map(([value, label]) => <option value={value} key={value}>{label}</option>)}</select></label>}
      <label>Password<input name="password" type="password" autoComplete={isRegister ? "new-password" : "current-password"} minLength="8" required value={form.password} onChange={update} />{isRegister && <small>Use at least 8 characters.</small>}</label>
      {isRegister && <label>Confirm password<input name="confirmation" type="password" autoComplete="new-password" minLength="8" required value={form.confirmation} onChange={update} /></label>}
      <Button disabled={busy}>{busy ? "Please wait..." : isRegister ? "Create my account" : "Sign in"} <i className="bi bi-arrow-right" /></Button>
      <div className="form-switch">{isRegister ? "Already have an account?" : "New to Careers React App?"} <button type="button" onClick={() => onNavigate({ page: isRegister ? "login" : "register" })}>{isRegister ? "Sign in" : "Create your account"}</button></div>
    </form>
  </section>;
}

function Assessment({ api, setBusy, busy, onNavigate, setMessage }) {
  const [step, setStep] = useState(0);
  const [answers, setAnswers] = useState(() => Object.fromEntries(questions.map((question) => [question.key, []])));
  const [careerStage, setCareerStage] = useState("");
  const [error, setError] = useState("");
  const question = questions[step];
  const toggle = (value) => {
    setAnswers((current) => {
      const selected = current[question.key];
      return { ...current, [question.key]: selected.includes(value) ? selected.filter((item) => item !== value) : [...selected, value] };
    });
    setError("");
  };
  const next = () => {
    if (answers[question.key].length === 0) {
      setError("Choose at least one answer before continuing.");
      return;
    }
    setError("");
    setStep((current) => Math.min(current + 1, questions.length - 1));
  };
  const submit = async (event) => {
    event.preventDefault();
    if (questions.some((item) => answers[item.key].length === 0)) {
      const firstEmpty = questions.findIndex((item) => answers[item.key].length === 0);
      setStep(firstEmpty);
      setError("Choose at least one answer in every section.");
      return;
    }
    if (!careerStage) {
      setError("Choose your current career stage to finish the assessment.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      await api("assessment", { method: "POST", body: { answers, career_stage: careerStage } });
      setMessage("Your answers are saved. Here are career directions that connect with them.", "success");
      onNavigate({ page: "results" });
    } catch (requestError) {
      setError(requestError.message);
    } finally {
      setBusy(false);
    }
  };
  const percent = Math.round(((step + 1) / questions.length) * 100);

  return <section className="assessment-wrap"><div className="page-heading"><Eyebrow>Your career discovery</Eyebrow><h1>Let’s find your direction.</h1><p>There are no right or wrong answers. Choose what feels true for you.</p></div>
    <form className="assessment-card" onSubmit={submit}>
      <div className="progress-row"><div><strong>Step {step + 1} of {questions.length}</strong><span>{question.label}</span></div><strong>{percent}%</strong></div><div className="progress-track"><span style={{ width: `${percent}%` }} /></div>
      <div className="question-heading"><span className="question-number">0{step + 1}</span><div><h2>{question.title}</h2><p>{question.description}</p></div></div>
      <div className="choice-grid">{question.options.map((option) => <label className={`choice-card${answers[question.key].includes(option) ? " is-selected" : ""}`} key={option}><input type="checkbox" checked={answers[question.key].includes(option)} onChange={() => toggle(option)} /><span className="choice-check"><i className="bi bi-check2" /></span>{option}</label>)}</div>
      {step === questions.length - 1 && <label className="stage-select">Your current stage<select value={careerStage} onChange={(event) => setCareerStage(event.target.value)} required><option value="">Choose one</option>{careerStages.map(([value, label]) => <option value={value} key={value}>{label}</option>)}</select></label>}
      <Alert message={error} />
      <div className="assessment-controls"><Button secondary type="button" disabled={step === 0} onClick={() => { setStep((current) => Math.max(current - 1, 0)); setError(""); }}><i className="bi bi-arrow-left" /> Back</Button>{step < questions.length - 1 ? <Button type="button" onClick={next}>Next step <i className="bi bi-arrow-right" /></Button> : <Button disabled={busy}>{busy ? "Saving answers..." : "See my career matches"} <i className="bi bi-stars" /></Button>}</div>
    </form>
  </section>;
}

function Dashboard({ data, onNavigate, api, setMessage }) {
  if (!data) return <LoadingState text="Loading your dashboard..." />;
  const recommendations = data.recommendations || [];
  return <section className="dashboard-wrap"><div className="welcome-banner"><div><Eyebrow>Your career road</Eyebrow><h1>Welcome back, {data.profile?.first_name || "there"}.</h1><p>Your journey is yours. Here's your next step.</p></div><button className="button button-primary" type="button" onClick={() => onNavigate({ page: "assessment" })}>{data.assessment ? "Update my assessment" : "Start my assessment"} <i className="bi bi-arrow-right" /></button></div>
    <div className="stat-grid"><Stat icon="bi-clipboard2-check" tone="blue" value={data.assessment ? "Complete" : "Not started"} label="Career assessment" /><Stat icon="bi-stars" tone="violet" value={recommendations.length ? "Ready" : "Discover"} label="Personalised matches" /><Stat icon="bi-bookmark-heart" tone="green" value={data.saved_count ?? 0} label="Saved careers" /><Stat icon="bi-check2-circle" tone="amber" value={data.completed_steps ?? 0} label="Steps completed" /></div>
    {recommendations.length === 0 ? <EmptyState title="Your career compass starts with you." copy="Answer a few questions about your interests, skills and goals to get career matches made for you." action="Begin my discovery" onAction={() => onNavigate({ page: "assessment" })} /> : <><div className="section-heading-row"><div><Eyebrow>Built around your answers</Eyebrow><h2>Your top career directions</h2><p>These matches connect to the interests and abilities you shared.</p></div><button className="text-button" type="button" onClick={() => onNavigate({ page: "results" })}>See all matches <i className="bi bi-arrow-right" /></button></div><CareerGrid careers={recommendations.slice(0, 3)} onNavigate={onNavigate} api={api} setMessage={setMessage} /></>}
    <div className="dashboard-links"><button type="button" onClick={() => onNavigate({ page: "explore" })}><i className="bi bi-search" /><span><strong>Explore career library</strong><small>Look through available career profiles.</small></span><i className="bi bi-arrow-right" /></button><button type="button" onClick={() => onNavigate({ page: "saved" })}><i className="bi bi-bookmark-heart" /><span><strong>Review saved careers</strong><small>Return to directions you want to explore.</small></span><i className="bi bi-arrow-right" /></button></div>
  </section>;
}

function Stat({ icon, tone, value, label }) {
  return <article className="stat-card"><span className={`stat-icon tone-${tone}`}><i className={`bi ${icon}`} /></span><div><strong>{value}</strong><small>{label}</small></div></article>;
}

function Results({ data, onNavigate, api, setMessage }) {
  if (!data) return <LoadingState text="Finding your career matches..." />;
  const careers = data.recommendations || [];
  return <section className="content-page"><div className="page-heading"><Eyebrow>Your personal career matches</Eyebrow><h1>Possibilities that start with you.</h1><p>Matches are ranked by how your interests, skills, subjects, work preferences and ambitions connect to each career.</p></div><div className="notice-box"><i className="bi bi-info-circle" /><span>A compatibility score is a starting point for exploration, not a qualification or a promise of employment.</span></div>{careers.length ? <CareerGrid careers={careers.slice(0, 5)} onNavigate={onNavigate} api={api} setMessage={setMessage} /> : <EmptyState title="Take your assessment to see your matches." copy="Your answers give us the details needed to personalise your career directions." action="Start assessment" onAction={() => onNavigate({ page: "assessment" })} />}</section>;
}

function Explore({ data, route, onNavigate, api, setMessage }) {
  const [search, setSearch] = useState(route.search);
  const [category, setCategory] = useState(route.category);
  const [categories, setCategories] = useState([]);
  useEffect(() => {
    setSearch(route.search);
    setCategory(route.category);
  }, [route.search, route.category]);
  useEffect(() => {
    if (!data?.careers) return;
    setCategories([...new Set(data.careers.map((career) => career.category))].sort());
  }, [data]);
  return <section className="content-page"><div className="page-heading"><Eyebrow>Career library</Eyebrow><h1>Explore what’s possible.</h1><p>Learn what different careers involve, where opportunities may exist and how to start building relevant skills.</p></div><form className="search-panel" onSubmit={(event) => { event.preventDefault(); onNavigate({ page: "explore", search, category }); }}><label className="search-input"><i className="bi bi-search" /><input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Search careers or areas of work" maxLength="80" /></label><select aria-label="Filter by career category" value={category} onChange={(event) => setCategory(event.target.value)}><option value="">All career areas</option>{categories.map((item) => <option key={item}>{item}</option>)}</select><Button>Search <i className="bi bi-arrow-right" /></Button></form>{!data ? <LoadingState text="Loading career profiles..." /> : data.careers?.length ? <><p className="results-count">{data.careers.length} career{data.careers.length === 1 ? "" : "s"} to explore</p><CareerGrid careers={data.careers} onNavigate={onNavigate} api={api} setMessage={setMessage} /></> : <EmptyState title="No matching careers found." copy="Try a different search or clear the career-area filter." action="Clear filters" onAction={() => onNavigate({ page: "explore" })} />}</section>;
}

function CareerGrid({ careers, onNavigate, api, setMessage }) {
  return <div className="career-grid">{careers.map((career) => <CareerCard key={career.id} career={career} onNavigate={onNavigate} api={api} setMessage={setMessage} />)}</div>;
}

function CareerCard({ career, onNavigate, api, setMessage }) {
  const [saved, setSaved] = useState(false);
  const [busy, setBusy] = useState(false);
  const matched = Object.values(career.matched || {}).flat().slice(0, 3);
  const toggleSave = async () => {
    setBusy(true);
    try {
      const result = await api("saved", { method: "POST", body: { career_id: career.id } });
      setSaved(result.saved);
      setMessage(result.saved ? "Career saved to your list." : "Career removed from your saved list.", "success");
    } catch (error) {
      setMessage(error.message);
    } finally {
      setBusy(false);
    }
  };
  return <article className="career-card"><div className="career-card-top"><span className="career-icon"><i className={`bi ${iconByCategory[career.category] || "bi-compass"}`} /></span><span className="career-category">{career.category}</span>{Number.isFinite(career.score) && <span className="match-score">{career.score}% match</span>}</div><h3><button type="button" onClick={() => onNavigate({ page: "career", slug: career.slug })}>{career.title}</button></h3><p>{career.summary}</p>{matched.length > 0 && <div className="match-tags">{matched.map((tag) => <span key={tag}>{tag}</span>)}</div>}<div className="career-card-actions"><button className="text-button" type="button" onClick={() => onNavigate({ page: "career", slug: career.slug })}>View career <i className="bi bi-arrow-up-right" /></button><button className={`save-button${saved ? " is-saved" : ""}`} type="button" aria-label={saved ? "Remove saved career" : "Save career"} disabled={busy} onClick={toggleSave}><i className={`bi ${saved ? "bi-bookmark-fill" : "bi-bookmark"}`} /></button></div></article>;
}

function CareerProfile({ career, loading, onNavigate, api, setMessage, session }) {
  if (loading) return <LoadingState text="Opening career profile..." />;
  if (!career) return <section className="content-page"><EmptyState title="Career not found." copy="Explore the career library to find another direction." action="Explore careers" onAction={() => onNavigate({ page: "explore" })} /></section>;
  return <section className="content-page"><button className="back-link" type="button" onClick={() => onNavigate({ page: "explore" })}><i className="bi bi-arrow-left" /> Career library</button><div className="profile-hero"><span className="career-icon profile-icon"><i className={`bi ${iconByCategory[career.category] || "bi-compass"}`} /></span><Eyebrow>{career.category}</Eyebrow><h1>{career.title}</h1><p>{career.summary}</p><div className="hero-actions"><button className="button button-primary" type="button" onClick={() => onNavigate({ page: session ? "assessment" : "register" })}>Find my career matches <i className="bi bi-arrow-right" /></button>{session && <button className="button button-secondary" type="button" onClick={() => onNavigate({ page: "roadmap", career: String(career.id) })}><i className="bi bi-signpost-split" /> View roadmap</button>}</div></div><div className="profile-grid"><ProfileCard title="What this career involves" label="THE ROLE" copy={career.responsibilities} wide /><ProfileCard title="Typical qualifications" label="GETTING STARTED" copy={career.qualifications} /><ProfileCard title="Useful skills" label="TRANSFERABLE STRENGTHS" tags={career.skills} /><ProfileCard title="Employment opportunities" label="WHERE TO LOOK" copy={career.opportunities} /><ProfileCard title="Challenges to consider" label="REALISTIC EXPECTATIONS" copy={career.challenges} /><ProfileCard title="Career growth" label="NEXT CHAPTER" copy={career.growth} /><ProfileCard title="Related interests" label="EXPLORE YOUR FIT" tags={career.interests} /></div></section>;
}

function ProfileCard({ label, title, copy, tags, wide }) {
  return <article className={`profile-card${wide ? " profile-wide" : ""}`}><Eyebrow>{label}</Eyebrow><h2>{title}</h2>{copy && <p>{copy}</p>}{tags && <div className="match-tags">{tags.map((tag) => <span key={tag}>{tag}</span>)}</div>}</article>;
}

function SavedCareers({ data, onNavigate, api, setMessage }) {
  if (!data) return <LoadingState text="Loading your saved careers..." />;
  return <section className="content-page"><div className="page-heading"><Eyebrow>Your shortlist</Eyebrow><h1>Saved careers.</h1><p>Keep interesting directions close while you explore what feels right for you.</p></div>{data.careers?.length ? <CareerGrid careers={data.careers} onNavigate={onNavigate} api={api} setMessage={setMessage} /> : <EmptyState title="Your shortlist starts here." copy="Save a career from its profile or explore the career library to find an interesting path." action="Explore careers" onAction={() => onNavigate({ page: "explore" })} />}</section>;
}

function Roadmap({ data, loading, api, setMessage }) {
  if (loading) return <LoadingState text="Loading your roadmap..." />;
  if (!data?.career) return <section className="content-page"><EmptyState title="Career roadmap not found." copy="Choose a career to see its development plan." action="Explore careers" onAction={() => { window.location.assign("?page=explore"); }} /></section>;
  const toggle = async (stepNumber) => {
    try {
      const result = await api("progress", { method: "POST", body: { career_id: data.career.id, step_number: stepNumber } });
      setMessage(result.completed ? "Roadmap progress saved." : "Roadmap step marked as not complete.", "success");
      window.dispatchEvent(new PopStateEvent("popstate"));
    } catch (error) {
      setMessage(error.message);
    }
  };
  const steps = data.career.roadmap || [];
  const completed = data.completed_steps || [];
  return <section className="content-page"><button className="back-link" type="button" onClick={() => { window.location.assign(`?page=career&slug=${encodeURIComponent(data.career.slug)}`); }}><i className="bi bi-arrow-left" /> {data.career.title}</button><div className="page-heading"><Eyebrow>Your development plan</Eyebrow><h1>A practical roadmap to {data.career.title}.</h1><p>Work through these suggested steps at your own pace. Your progress is saved to your account.</p></div><div className="roadmap-summary"><strong>{completed.length} of {steps.length} steps complete</strong><span className="roadmap-track"><i style={{ width: `${steps.length ? Math.round((completed.length / steps.length) * 100) : 0}%` }} /></span></div><ol className="roadmap-list">{steps.map((step, index) => <li className={`roadmap-item${completed.includes(index) ? " is-complete" : ""}`} key={step}><span className="roadmap-number">{String(index + 1).padStart(2, "0")}</span><div><h2>{step}</h2><p>Take this step when it makes sense for your goals and current experience.</p></div><button className="progress-toggle" type="button" onClick={() => toggle(index)}><i className={`bi ${completed.includes(index) ? "bi-check-circle-fill" : "bi-circle"}`} /> {completed.includes(index) ? "Completed" : "Mark complete"}</button></li>)}</ol></section>;
}

function JobsPage({ onNavigate }) {
  return <section className="content-page jobs-page"><div className="page-heading"><Eyebrow>Kenyan job search sites</Eyebrow><h1>Explore current vacancies.</h1><p>Visit these job boards to search vacancies in Kenya. Each website manages its own listings, applications and closing dates.</p></div><div className="notice-box"><i className="bi bi-info-circle" /><span>These links take you to external job websites. Listings can change or close at any time, so confirm details on the original site.</span></div><div className="job-board-grid">{jobBoards.map((board) => <article className="job-board-card" key={board.title}><span className={`job-board-icon tone-${board.tone}`}><i className={`bi ${board.icon}`} /></span><span className="job-board-label">{board.label}</span><h2>{board.title}</h2><p>{board.description}</p><a className="button button-primary" href={board.url} target="_blank" rel="noopener noreferrer">{board.action} <i className="bi bi-box-arrow-up-right" /></a><span className="job-board-domain">{board.domain}</span></article>)}</div><div className="jobs-career-cta"><span><strong>Not sure which roles to search for?</strong><small>Explore your interests and career paths that may suit you.</small></span><button className="text-button" type="button" onClick={() => onNavigate({ page: "explore" })}>Explore career profiles <i className="bi bi-arrow-right" /></button></div></section>;
}

function EmptyState({ title, copy, action, onAction }) {
  return <div className="empty-state"><span className="empty-icon"><i className="bi bi-compass" /></span><h2>{title}</h2><p>{copy}</p>{action && <button className="button button-primary" type="button" onClick={onAction}>{action} <i className="bi bi-arrow-right" /></button>}</div>;
}

function LoadingState({ text }) {
  return <div className="loading-state"><span className="loading-spinner" /><p>{text}</p></div>;
}

export default App;
