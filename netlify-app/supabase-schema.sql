create extension if not exists pgcrypto;

create table if not exists public.profiles (
  id uuid primary key references auth.users (id) on delete cascade,
  first_name text not null,
  last_name text not null,
  career_stage text not null check (career_stage in (
    'secondary_student', 'college_student', 'university_student',
    'graduate', 'working_professional', 'career_changer'
  )),
  created_at timestamptz not null default now()
);

create table if not exists public.careers (
  id bigint generated always as identity primary key,
  slug text not null unique,
  title text not null,
  category text not null,
  summary text not null,
  responsibilities text not null,
  qualifications text not null,
  skills jsonb not null default '[]'::jsonb,
  interests jsonb not null default '[]'::jsonb,
  subjects jsonb not null default '[]'::jsonb,
  work_styles jsonb not null default '[]'::jsonb,
  goals jsonb not null default '[]'::jsonb,
  opportunities text not null,
  challenges text not null,
  growth text not null,
  roadmap jsonb not null default '[]'::jsonb,
  featured boolean not null default true,
  created_at timestamptz not null default now()
);

create table if not exists public.assessments (
  id bigint generated always as identity primary key,
  user_id uuid not null unique references auth.users (id) on delete cascade,
  answers jsonb not null,
  completed_at timestamptz not null default now()
);

create table if not exists public.recommendations (
  user_id uuid not null references auth.users (id) on delete cascade,
  career_id bigint not null references public.careers (id) on delete cascade,
  score integer not null check (score between 0 and 100),
  matched jsonb not null default '{}'::jsonb,
  created_at timestamptz not null default now(),
  primary key (user_id, career_id)
);

create table if not exists public.saved_careers (
  user_id uuid not null references auth.users (id) on delete cascade,
  career_id bigint not null references public.careers (id) on delete cascade,
  created_at timestamptz not null default now(),
  primary key (user_id, career_id)
);

create table if not exists public.roadmap_progress (
  user_id uuid not null references auth.users (id) on delete cascade,
  career_id bigint not null references public.careers (id) on delete cascade,
  step_number integer not null check (step_number between 0 and 20),
  completed_at timestamptz not null default now(),
  primary key (user_id, career_id, step_number)
);

alter table public.profiles enable row level security;
alter table public.careers enable row level security;
alter table public.assessments enable row level security;
alter table public.recommendations enable row level security;
alter table public.saved_careers enable row level security;
alter table public.roadmap_progress enable row level security;

drop policy if exists "Users can read their own profile" on public.profiles;
create policy "Users can read their own profile"
  on public.profiles for select to authenticated using ((select auth.uid()) = id);

drop policy if exists "Users can update their own profile" on public.profiles;
create policy "Users can update their own profile"
  on public.profiles for update to authenticated using ((select auth.uid()) = id)
  with check ((select auth.uid()) = id);

drop policy if exists "Anyone can read published careers" on public.careers;
create policy "Anyone can read published careers"
  on public.careers for select using (featured = true);

drop policy if exists "Users can read their own assessment" on public.assessments;
create policy "Users can read their own assessment"
  on public.assessments for select to authenticated using ((select auth.uid()) = user_id);

drop policy if exists "Users can read their own recommendations" on public.recommendations;
create policy "Users can read their own recommendations"
  on public.recommendations for select to authenticated using ((select auth.uid()) = user_id);

drop policy if exists "Users can read their own saved careers" on public.saved_careers;
create policy "Users can read their own saved careers"
  on public.saved_careers for select to authenticated using ((select auth.uid()) = user_id);

drop policy if exists "Users can read their own roadmap progress" on public.roadmap_progress;
create policy "Users can read their own roadmap progress"
  on public.roadmap_progress for select to authenticated using ((select auth.uid()) = user_id);

create or replace function public.create_career_profile()
returns trigger
language plpgsql
security definer
set search_path = ''
as $$
begin
  insert into public.profiles (id, first_name, last_name, career_stage)
  values (
    new.id,
    coalesce(new.raw_user_meta_data ->> 'first_name', ''),
    coalesce(new.raw_user_meta_data ->> 'last_name', ''),
    coalesce(new.raw_user_meta_data ->> 'career_stage', 'career_changer')
  );
  return new;
end;
$$;

drop trigger if exists on_auth_user_created_career_road on auth.users;
create trigger on_auth_user_created_career_road
  after insert on auth.users
  for each row execute procedure public.create_career_profile();

insert into public.careers (
  slug, title, category, summary, responsibilities, qualifications, skills, interests,
  subjects, work_styles, goals, opportunities, challenges, growth, roadmap
) values
(
  'software-developer', 'Software Developer', 'Technology',
  'Designs and builds software that solves practical problems for people and organisations.',
  'Plan and build applications; write and test code; fix issues; collaborate with a team; improve software based on user feedback.',
  'A diploma or degree in computing can help. Many employers also value demonstrable programming ability, a practical project portfolio and continued learning.',
  '["Programming","Problem solving","Communication","Analytical thinking"]',
  '["Building technology","Solving problems","Creating things"]',
  '["Mathematics","Computer Studies","Physics"]',
  '["Independent focus","Collaborative teamwork","Flexible schedule","Remote or hybrid"]',
  '["Build digital products","Solve community problems","Start a business"]',
  'Technology firms, banks, telecommunications, government, consultancies, and software teams across many industries.',
  'Technology changes quickly; finding the cause of software bugs can take patience; teams must balance quality with deadlines.',
  'Junior developer → software developer → senior developer → technical lead, architect or engineering manager.',
  '["Practise programming foundations: variables, decisions, loops and functions.","Build responsive pages with semantic HTML and accessible CSS.","Add JavaScript interactions and validate real form input.","Learn server-side development and connect forms to a relational database.","Complete and document a small project for a portfolio."]'
),
(
  'data-analyst', 'Data Analyst', 'Technology',
  'Finds useful patterns in information and explains them so teams can make better decisions.',
  'Collect and organise data; check its quality; find trends; create clear charts and reports; explain findings to stakeholders.',
  'Study in mathematics, statistics, business or computing is useful. Spreadsheet, SQL and data-visualisation skills are commonly requested.',
  '["Data analysis","Analytical thinking","Problem solving","Communication"]',
  '["Working with data","Solving problems","Researching ideas"]',
  '["Mathematics","Computer Studies","Business Studies"]',
  '["Independent focus","Collaborative teamwork","Structured work","Remote or hybrid"]',
  '["Work with data","Solve community problems","Build digital products"]',
  'Banks, research organisations, retailers, public agencies, NGOs, telecommunications and technology companies.',
  'Data may be incomplete or inconsistent; accurate analysis and clear communication are equally important.',
  'Analyst → senior analyst → business-intelligence specialist, data scientist or analytics manager.',
  '["Strengthen spreadsheet and basic statistics foundations.","Practise cleaning and summarising a small real dataset.","Learn SQL to query information stored in a database.","Create useful charts and a short, clear findings report.","Publish a documented analysis project in a portfolio."]'
),
(
  'ui-ux-designer', 'UI/UX Designer', 'Creative work',
  'Makes digital products easier to understand and more useful through research, structure and visual design.',
  'Understand what people need; sketch ideas; design screen flows; prototype interfaces; gather feedback; work with developers.',
  'Design training is helpful, but employers also look for thoughtful case studies, a portfolio and user-centred problem solving.',
  '["Visual design","Communication","Problem solving","Research"]',
  '["Creating visual designs","Helping people","Building technology","Creating things"]',
  '["Art and Design","Computer Studies","English"]',
  '["Independent focus","Collaborative teamwork","Flexible schedule","Remote or hybrid"]',
  '["Build digital products","Help people directly","Start a business"]',
  'Software companies, design studios, digital agencies, financial services, NGOs and in-house product teams.',
  'Design must serve users and business needs; feedback and changing requirements are a normal part of the work.',
  'Junior designer → product designer → senior designer → design lead, researcher or product manager.',
  '["Observe an everyday task and write down where it feels difficult.","Learn basic visual hierarchy, typography and colour contrast.","Sketch a simpler screen flow that addresses the user problem.","Make a clickable prototype and ask a few people to try it.","Write up the problem, your decisions and feedback as a case study."]'
),
(
  'digital-marketer', 'Digital Marketer', 'Business',
  'Helps organisations reach the right people through useful content, campaigns and digital channels.',
  'Plan campaigns; write and schedule content; understand audiences; review performance; improve communication across online channels.',
  'Marketing, business or communication studies can be useful. Employers value clear writing, digital-platform knowledge and examples of results.',
  '["Communication","Content creation","Data analysis","Organisation"]',
  '["Writing and communication","Creating visual designs","Working with data","Starting projects"]',
  '["Business Studies","English","Art and Design"]',
  '["Collaborative teamwork","Flexible schedule","Structured work","Remote or hybrid"]',
  '["Grow a business","Start a business","Build digital products"]',
  'Businesses, creative agencies, ecommerce, non-profits, startups and communications teams.',
  'Platforms and audience behaviour change; effective campaigns require testing, ethical communication and learning from results.',
  'Marketing assistant → digital marketer → campaign strategist, growth marketer or marketing lead.',
  '["Choose a community project or small business and understand its audience.","Practise writing short, clear posts with an accessible visual.","Learn basic search, email and social-media marketing concepts.","Plan a small campaign with a clear goal and schedule.","Review results and build a short portfolio case study."]'
),
(
  'teacher-educator', 'Teacher & Educator', 'Education',
  'Helps people learn by explaining ideas, adapting lessons and creating supportive learning environments.',
  'Plan lessons; explain subject concepts; check understanding; give constructive feedback; support different learning needs.',
  'Teaching roles generally require recognised education qualifications and registration under the rules applicable to the role and country.',
  '["Communication","Organisation","Research","Teamwork"]',
  '["Helping people","Researching ideas","Writing and communication","Share knowledge"]',
  '["English","Mathematics","Biology"]',
  '["Collaborative teamwork","Structured work","Hands-on practice"]',
  '["Help people directly","Solve community problems","Share knowledge"]',
  'Schools, colleges, training organisations, community programmes and education-technology projects.',
  'Learners have different needs; preparation, patience and supportive classroom communication matter every day.',
  'Classroom educator → subject specialist → department lead, instructional designer or education leader.',
  '["Explore the subjects and age groups you most enjoy supporting.","Practise explaining a topic in a short lesson for a beginner.","Research teacher-training and registration requirements where you plan to work.","Ask an educator about placements, mentoring and classroom experience.","Build a record of lesson plans, teaching practice and feedback."]'
),
(
  'entrepreneur', 'Entrepreneur', 'Entrepreneurship',
  'Turns a useful idea into a venture by understanding customers, testing solutions and managing uncertainty.',
  'Spot a need; talk to potential customers; test ideas; manage finances and operations; learn from outcomes.',
  'There is no single required qualification for starting a venture. Business, financial and industry knowledge can help; local legal requirements still apply.',
  '["Problem solving","Communication","Organisation","Data analysis"]',
  '["Starting projects","Solving problems","Improving processes","Creating things"]',
  '["Business Studies","Mathematics","English"]',
  '["Flexible schedule","Independent focus","Hands-on practice"]',
  '["Start a business","Grow a business","Solve community problems"]',
  'Self-employment, small business, social enterprise, creative ventures and technology startups.',
  'Income may be uncertain; customer needs, costs and business assumptions should be tested before investing heavily.',
  'Idea validation → small pilot → sustainable operations → responsible growth or new ventures.',
  '["Choose a real problem experienced by people you can talk to.","Interview potential customers before deciding on a solution.","Estimate costs and test a small, low-cost version of the idea.","Track feedback, spending and results in a simple business plan.","Learn relevant licensing and tax requirements before scaling."]'
),
(
  'agricultural-entrepreneur', 'Agriculture & Agribusiness Specialist', 'Agriculture',
  'Applies practical, scientific and business knowledge to strengthen food and agricultural systems.',
  'Support crop or livestock production; understand supply chains; apply relevant technology; monitor quality and costs; share practical advice.',
  'Agricultural training is useful for many specialist roles. Entry requirements differ between agribusiness, extension and technical positions.',
  '["Research","Problem solving","Organisation","Data analysis"]',
  '["Working outdoors","Improving processes","Solving problems","Starting projects"]',
  '["Biology","Chemistry","Mathematics","Geography"]',
  '["Hands-on practice","Collaborative teamwork","Flexible schedule"]',
  '["Solve community problems","Grow a business","Help people directly"]',
  'Farms, cooperatives, food businesses, agricultural technology, research, public extension and development programmes.',
  'Work is affected by weather, market conditions and local resources; practical knowledge must fit local conditions.',
  'Field assistant → technical or agribusiness specialist → operations lead, consultant or venture founder.',
  '["Explore local crops, food systems and challenges in your community.","Learn safe and sustainable production practices from a trusted source.","Visit a farm, cooperative or agribusiness and ask about daily work.","Track a small production or market activity with simple records.","Identify relevant training, mentors and local business requirements."]'
),
(
  'community-health-worker', 'Community Health Worker', 'Healthcare',
  'Connects communities with health information, preventive support and appropriate health services.',
  'Share approved health information; listen to community needs; connect people with qualified services; maintain privacy and records.',
  'Training, certification and scope-of-practice requirements vary by location and employer. This role does not replace licensed clinical care.',
  '["Communication","Teamwork","Organisation","Research"]',
  '["Helping people","Solving community problems","Researching ideas"]',
  '["Biology","English","Chemistry"]',
  '["Collaborative teamwork","Hands-on practice","Structured work"]',
  '["Help people directly","Solve community problems","Share knowledge"]',
  'Community health programmes, public health organisations, NGOs and local service networks.',
  'The work requires confidentiality, cultural sensitivity, clear boundaries and referral to qualified professionals when needed.',
  'Community health worker → programme coordinator, public health training or further regulated health study.',
  '["Learn about local community health roles and their legal scope.","Practise respectful listening and clear, non-judgmental communication.","Find recognised training and safeguarding requirements in your area.","Ask a reputable health organisation about supervised volunteering.","Keep professional boundaries and refer clinical questions to licensed staff."]'
),
(
  'graphic-designer', 'Graphic Designer', 'Creative work',
  'Creates visual materials that help brands and organisations communicate their ideas clearly.',
  'Develop design concepts; create digital or print layouts; respond to feedback; maintain visual consistency across projects.',
  'Design study can help; a strong portfolio, attention to detail and knowledge of design software are often valued.',
  '["Visual design","Content creation","Communication","Organisation"]',
  '["Creating visual designs","Creating things","Writing and communication"]',
  '["Art and Design","English","Business Studies"]',
  '["Independent focus","Collaborative teamwork","Flexible schedule"]',
  '["Build digital products","Grow a business","Start a business"]',
  'Design agencies, publishers, technology companies, NGOs, small businesses and freelance work.',
  'Projects can involve tight deadlines and changing feedback; the final design must meet the audience and brief.',
  'Junior designer → senior designer → art director, creative lead or independent studio owner.',
  '["Practise composition, accessible colour contrast and typography.","Choose a local communication challenge and research its audience.","Create a few alternatives and ask others which is clearest and why.","Build a polished sample for digital and print use.","Document your process and assemble a small portfolio."]'
)
on conflict (slug) do update set
  title = excluded.title,
  category = excluded.category,
  summary = excluded.summary,
  responsibilities = excluded.responsibilities,
  qualifications = excluded.qualifications,
  skills = excluded.skills,
  interests = excluded.interests,
  subjects = excluded.subjects,
  work_styles = excluded.work_styles,
  goals = excluded.goals,
  opportunities = excluded.opportunities,
  challenges = excluded.challenges,
  growth = excluded.growth,
  roadmap = excluded.roadmap,
  featured = true;
