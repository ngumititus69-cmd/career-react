import { createClient } from "@supabase/supabase-js";

const optionSets = {
  interests: [
    "Solving problems",
    "Building technology",
    "Working with data",
    "Helping people",
    "Creating visual designs",
    "Starting projects",
    "Researching ideas",
    "Writing and communication",
    "Improving processes",
    "Creating things",
    "Working outdoors",
    "Share knowledge",
  ],
  skills: [
    "Programming",
    "Problem solving",
    "Communication",
    "Analytical thinking",
    "Visual design",
    "Data analysis",
    "Content creation",
    "Organisation",
    "Research",
    "Teamwork",
  ],
  subjects: [
    "Mathematics",
    "Computer Studies",
    "Physics",
    "Business Studies",
    "English",
    "Art and Design",
    "Biology",
    "Chemistry",
    "Geography",
  ],
  work_styles: [
    "Independent focus",
    "Collaborative teamwork",
    "Flexible schedule",
    "Structured work",
    "Hands-on practice",
    "Remote or hybrid",
  ],
  goals: [
    "Build digital products",
    "Work with data",
    "Help people directly",
    "Grow a business",
    "Start a business",
    "Solve community problems",
    "Share knowledge",
  ],
};

const stages = new Set([
  "secondary_student",
  "college_student",
  "university_student",
  "graduate",
  "working_professional",
  "career_changer",
]);

const weights = {
  interests: 40,
  skills: 25,
  subjects: 15,
  work_styles: 10,
  goals: 10,
};

function jsonResponse(body, status = 200) {
  return new Response(JSON.stringify(body), {
    status,
    headers: {
      "content-type": "application/json; charset=utf-8",
      "cache-control": "no-store",
      "x-content-type-options": "nosniff",
    },
  });
}

function getClients() {
  const url = Netlify.env.get("SUPABASE_URL");
  const anonKey = Netlify.env.get("SUPABASE_ANON_KEY");
  const serviceKey = Netlify.env.get("SUPABASE_SERVICE_ROLE_KEY");
  if (!url || !anonKey || !serviceKey) {
    throw new Error("Supabase environment variables are not configured.");
  }

  return {
    authClient: createClient(url, anonKey, {
      auth: { persistSession: false, autoRefreshToken: false },
    }),
    adminClient: createClient(url, serviceKey, {
      auth: { persistSession: false, autoRefreshToken: false },
    }),
  };
}

function safeCareer(career) {
  return {
    id: career.id,
    slug: career.slug,
    title: career.title,
    category: career.category,
    summary: career.summary,
    responsibilities: career.responsibilities,
    qualifications: career.qualifications,
    skills: career.skills,
    interests: career.interests,
    subjects: career.subjects,
    work_styles: career.work_styles,
    goals: career.goals,
    opportunities: career.opportunities,
    challenges: career.challenges,
    growth: career.growth,
    roadmap: career.roadmap,
  };
}

function scoreCareer(career, answers) {
  let earned = 0;
  let available = 0;
  const matched = {};

  for (const [field, weight] of Object.entries(weights)) {
    const chosen = Array.isArray(answers[field]) ? answers[field] : [];
    const required = Array.isArray(career[field]) ? career[field] : [];
    if (chosen.length === 0 || required.length === 0) continue;

    available += weight;
    const hits = chosen.filter((value) => required.includes(value));
    earned += weight * (hits.length / required.length);
    if (hits.length > 0) matched[field] = hits;
  }

  return {
    career_id: career.id,
    score: available === 0 ? 0 : Math.round((earned / available) * 100),
    matched,
  };
}

async function requireUser(request, authClient) {
  const authorization = request.headers.get("authorization") ?? "";
  const match = authorization.match(/^Bearer\s+(.+)$/i);
  if (!match) return { error: jsonResponse({ error: "Please sign in to continue." }, 401) };

  const { data, error } = await authClient.auth.getUser(match[1]);
  if (error || !data.user) {
    return { error: jsonResponse({ error: "Your session has expired. Please sign in again." }, 401) };
  }

  return { user: data.user };
}

async function getCareer(adminClient, id) {
  const { data, error } = await adminClient
    .from("careers")
    .select("*")
    .eq("id", id)
    .eq("featured", true)
    .maybeSingle();
  if (error) throw error;
  return data;
}

export default async (request) => {
  try {
    const { authClient, adminClient } = getClients();
    const url = new URL(request.url);
    const action = url.searchParams.get("action") ?? "";

    if (request.method === "GET" && action === "careers") {
      const search = (url.searchParams.get("search") ?? "").trim().slice(0, 80);
      const category = (url.searchParams.get("category") ?? "").trim().slice(0, 80);
      const createCareerQuery = () => {
        let query = adminClient.from("careers").select("*").eq("featured", true);
        if (category) query = query.eq("category", category);
        return query.order("category").order("title");
      };

      if (search) {
        const pattern = `%${search}%`;
        const [titleMatches, summaryMatches] = await Promise.all([
          createCareerQuery().ilike("title", pattern),
          createCareerQuery().ilike("summary", pattern),
        ]);
        if (titleMatches.error) throw titleMatches.error;
        if (summaryMatches.error) throw summaryMatches.error;
        const matches = new Map(
          [...(titleMatches.data ?? []), ...(summaryMatches.data ?? [])].map((career) => [career.id, career]),
        );
        return jsonResponse({ careers: [...matches.values()].map(safeCareer) });
      }

      const { data, error } = await createCareerQuery();
      if (error) throw error;
      return jsonResponse({ careers: (data ?? []).map(safeCareer) });
    }

    if (request.method === "GET" && action === "career") {
      const slug = url.searchParams.get("slug");
      if (!slug || slug.length > 100) return jsonResponse({ error: "Career not found." }, 404);
      const { data, error } = await adminClient
        .from("careers")
        .select("*")
        .eq("slug", slug)
        .eq("featured", true)
        .maybeSingle();
      if (error) throw error;
      return data ? jsonResponse({ career: safeCareer(data) }) : jsonResponse({ error: "Career not found." }, 404);
    }

    const authenticated = await requireUser(request, authClient);
    if (authenticated.error) return authenticated.error;
    const user = authenticated.user;

    if (request.method === "GET" && action === "dashboard") {
      const [profileResult, assessmentResult, recommendationResult, savedResult, progressResult] =
        await Promise.all([
          adminClient.from("profiles").select("first_name,last_name,career_stage").eq("id", user.id).maybeSingle(),
          adminClient.from("assessments").select("answers,completed_at").eq("user_id", user.id).maybeSingle(),
          adminClient
            .from("recommendations")
            .select("score,matched,career:careers(*)")
            .eq("user_id", user.id)
            .order("score", { ascending: false }),
          adminClient.from("saved_careers").select("career_id", { count: "exact", head: true }).eq("user_id", user.id),
          adminClient.from("roadmap_progress").select("career_id,step_number", { count: "exact" }).eq("user_id", user.id),
        ]);
      for (const result of [profileResult, assessmentResult, recommendationResult, savedResult, progressResult]) {
        if (result.error) throw result.error;
      }

      return jsonResponse({
        profile: profileResult.data,
        assessment: assessmentResult.data,
        recommendations: (recommendationResult.data ?? [])
          .filter((row) => row.career)
          .map((row) => ({ ...safeCareer(row.career), score: row.score, matched: row.matched })),
        saved_count: savedResult.count ?? 0,
        completed_steps: progressResult.count ?? 0,
      });
    }

    if (request.method === "POST" && action === "assessment") {
      const body = await request.json();
      if (!body || !body.answers || typeof body.answers !== "object" || !stages.has(body.career_stage)) {
        return jsonResponse({ error: "Please check your assessment answers and career stage." }, 400);
      }

      const answers = {};
      for (const [field, permitted] of Object.entries(optionSets)) {
        const selected = body.answers[field];
        if (!Array.isArray(selected)) {
          return jsonResponse({ error: "Please answer every section of the assessment." }, 400);
        }
        const normalized = [...new Set(selected.filter((value) => typeof value === "string" && permitted.includes(value)))];
        if (normalized.length === 0 || normalized.length !== selected.length) {
          return jsonResponse({ error: "Please choose at least one valid answer in every section." }, 400);
        }
        answers[field] = normalized;
      }
      answers.career_stage = body.career_stage;

      const { data: careerRows, error: careersError } = await adminClient
        .from("careers")
        .select("*")
        .eq("featured", true);
      if (careersError) throw careersError;

      const scored = careerRows.map((career) => scoreCareer(career, answers));
      const { error: assessmentError } = await adminClient.from("assessments").upsert(
        { user_id: user.id, answers, completed_at: new Date().toISOString() },
        { onConflict: "user_id" },
      );
      if (assessmentError) throw assessmentError;

      const { error: profileError } = await adminClient
        .from("profiles")
        .update({ career_stage: body.career_stage })
        .eq("id", user.id);
      if (profileError) throw profileError;

      const { error: deleteError } = await adminClient.from("recommendations").delete().eq("user_id", user.id);
      if (deleteError) throw deleteError;
      const { error: insertError } = await adminClient.from("recommendations").insert(
        scored.map((result) => ({ user_id: user.id, ...result })),
      );
      if (insertError) throw insertError;

      const results = scored
        .sort((left, right) => right.score - left.score)
        .slice(0, 5)
        .map((result) => {
          const career = careerRows.find((row) => row.id === result.career_id);
          return { ...safeCareer(career), score: result.score, matched: result.matched };
        });
      return jsonResponse({ recommendations: results });
    }

    if (request.method === "GET" && action === "saved") {
      const { data, error } = await adminClient
        .from("saved_careers")
        .select("career:careers(*)")
        .eq("user_id", user.id)
        .order("created_at", { ascending: false });
      if (error) throw error;
      return jsonResponse({ careers: (data ?? []).filter((row) => row.career).map((row) => safeCareer(row.career)) });
    }

    if (request.method === "GET" && action === "progress") {
      const careerId = Number(url.searchParams.get("career_id"));
      if (!Number.isSafeInteger(careerId) || careerId < 1) {
        return jsonResponse({ error: "Choose a valid career roadmap." }, 400);
      }
      const career = await getCareer(adminClient, careerId);
      if (!career) return jsonResponse({ error: "Career roadmap not found." }, 404);
      const { data: completedRows, error } = await adminClient
        .from("roadmap_progress")
        .select("step_number")
        .eq("user_id", user.id)
        .eq("career_id", careerId);
      if (error) throw error;
      return jsonResponse({
        career: safeCareer(career),
        completed_steps: (completedRows ?? []).map((row) => row.step_number),
      });
    }

    if (request.method === "POST" && action === "saved") {
      const body = await request.json();
      const careerId = Number(body?.career_id);
      if (!Number.isSafeInteger(careerId) || careerId < 1) {
        return jsonResponse({ error: "Choose a valid career." }, 400);
      }
      const career = await getCareer(adminClient, careerId);
      if (!career) return jsonResponse({ error: "Career not found." }, 404);

      const { data: existing, error: lookupError } = await adminClient
        .from("saved_careers")
        .select("career_id")
        .eq("user_id", user.id)
        .eq("career_id", careerId)
        .maybeSingle();
      if (lookupError) throw lookupError;
      if (existing) {
        const { error } = await adminClient
          .from("saved_careers")
          .delete()
          .eq("user_id", user.id)
          .eq("career_id", careerId);
        if (error) throw error;
        return jsonResponse({ saved: false });
      }

      const { error } = await adminClient.from("saved_careers").insert({
        user_id: user.id,
        career_id: careerId,
      });
      if (error) throw error;
      return jsonResponse({ saved: true });
    }

    if (request.method === "POST" && action === "progress") {
      const body = await request.json();
      const careerId = Number(body?.career_id);
      const stepNumber = Number(body?.step_number);
      if (!Number.isSafeInteger(careerId) || !Number.isSafeInteger(stepNumber) || stepNumber < 0 || stepNumber > 20) {
        return jsonResponse({ error: "Choose a valid roadmap step." }, 400);
      }
      const career = await getCareer(adminClient, careerId);
      if (!career || !Array.isArray(career.roadmap) || stepNumber >= career.roadmap.length) {
        return jsonResponse({ error: "Roadmap step not found." }, 404);
      }

      const { data: existing, error: lookupError } = await adminClient
        .from("roadmap_progress")
        .select("step_number")
        .eq("user_id", user.id)
        .eq("career_id", careerId)
        .eq("step_number", stepNumber)
        .maybeSingle();
      if (lookupError) throw lookupError;
      if (existing) {
        const { error } = await adminClient
          .from("roadmap_progress")
          .delete()
          .eq("user_id", user.id)
          .eq("career_id", careerId)
          .eq("step_number", stepNumber);
        if (error) throw error;
        return jsonResponse({ completed: false });
      }
      const { error } = await adminClient.from("roadmap_progress").insert({
        user_id: user.id,
        career_id: careerId,
        step_number: stepNumber,
      });
      if (error) throw error;
      return jsonResponse({ completed: true });
    }

    return jsonResponse({ error: "Endpoint not found." }, 404);
  } catch (error) {
    console.error("Career Road Netlify function failed:", error);
    return jsonResponse({ error: "The service could not complete that request. Please try again." }, 500);
  }
};
