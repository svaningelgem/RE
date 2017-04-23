#include "stdafx.h"
#include "test.h"


string LIBRARY_API stringVar;
int LIBRARY_API intVar;
double LIBRARY_API doubleVar;

SUBCLASSDEF(MAIN, SUB);

LIBRARY_API void * exportedFunction( int ) {
  return 0;
}

extern "C" LIBRARY_API void * externCFunction( int ) {
  return 0;
}
