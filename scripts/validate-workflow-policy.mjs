#!/usr/bin/env node

import { readFileSync } from "node:fs";
import { resolve } from "node:path";

const workflowFiles = {
  ".github/workflows/deploy.yml": {
    mustNotContain: [/\bmain\b/],
    mustContain: [/\bpush\s*:/, /\bmaster\b/, /\bworkflow_dispatch\s*:/, /actions\/deploy-pages@v4/],
  },
  ".github/workflows/blog-publish.yml": {
    mustNotContain: [/\bmain\b/, /\bpush\s*:/, /actions\/deploy-pages@v4/],
    mustContain: [/\bpull_request\s*:/],
  },
};

const failures = [];

for (const [relativePath, policy] of Object.entries(workflowFiles)) {
  const path = resolve(process.cwd(), relativePath);
  const content = readFileSync(path, "utf8");

  for (const pattern of policy.mustNotContain) {
    if (pattern.test(content)) {
      failures.push(`${relativePath}: contains forbidden pattern ${pattern}`);
    }
  }

  for (const pattern of policy.mustContain) {
    if (!pattern.test(content)) {
      failures.push(`${relativePath}: missing required pattern ${pattern}`);
    }
  }
}

if (failures.length > 0) {
  console.error("Workflow branch policy validation failed:");
  for (const failure of failures) {
    console.error(`- ${failure}`);
  }
  process.exit(1);
}

console.log("Workflow branch policy validation passed.");
